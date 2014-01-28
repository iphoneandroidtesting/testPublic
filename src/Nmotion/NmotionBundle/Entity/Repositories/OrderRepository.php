<?php

namespace Nmotion\NmotionBundle\Entity\Repositories;

use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Nmotion\NmotionBundle\Entity;
use Nmotion\NmotionBundle\Entity\Order;
use Nmotion\NmotionBundle\Entity\Restaurant;

/**
 * Class OrderRepository
 *
 * @method Order|null findOneByRestaurant(Restaurant $restaurant)
 */
class OrderRepository extends EntityRepository
{
    /**
     * get list of orders in particular status with all related entities included
     *
     * @param int $status
     * @param int | null $restaurantId
     * @return array
     */
    public function getFullOrdersWithStatus($status = Entity\OrderStatus::PAID, $restaurantId = null)
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o', 'ou', 'os', 'om', 'm', 'mes', 'me', 'mo')
            ->join('o.orderMeals', 'om')
            ->join('om.meal', 'm')
            ->leftJoin('om.orderMealExtraIngredients', 'mes')
            ->leftJoin('mes.mealExtraIngredient', 'me')
            ->leftJoin('om.mealOption', 'mo')
            ->leftJoin('o.slaves', 'os', 'WITH', 'os.orderStatus = :orderStatus')
            ->leftJoin('o.user', 'ou')
            ->where('o.orderStatus = :orderStatus')
            ->andWhere('o.master is null')
            ->setParameter('orderStatus', $status);

        if ((int)$restaurantId) {
            $qb->andWhere('o.restaurant = :restaurant')->setParameter('restaurant', $restaurantId);
        }

        /** @var Order[] $orders */
        $orders = $qb->getQuery()->getResult();
        foreach ($orders as $order) {
            $slaves = $order->getSlaves();
            if (!empty($slaves)) {
                $orderSlaves = [];
                foreach ($slaves as $slave) {
                    $orderSlaves[] = $this->getFullOrder($slave->getId());
                }
                $order->setSlaves($orderSlaves);
            }
        }

        return $orders;
    }

    /**
     * Get list of orders for particular period.
     *
     * @param int|DateTime $dateFrom
     * @param int|DateTime $dateTo
     * @param array        $orderBy optional
     * @param int          $limit   optional
     * @param int          $offset  optional
     *
     * @return array
     */
    public function findForPeriod($dateFrom, $dateTo, array $orderBy = null, $limit = null, $offset = null)
    {
        $dateFrom = $dateFrom instanceof DateTime ? $dateFrom->getTimestamp() : $dateFrom;
        $dateTo   = $dateTo instanceof DateTime ? $dateTo->getTimestamp() : $dateTo;

        $criteria = (new Criteria(null, $orderBy, $offset, $limit))
            ->where(Criteria::expr()->gte('updatedAt', $dateFrom))
            ->andWhere(Criteria::expr()->lte('updatedAt', $dateTo));

        return $this->matching($criteria);
    }

    /**
     * fetch full order data from DB
     *
     * @param $orderId
     * @return array
     */
    public function getFullOrder($orderId)
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'ou', 'om', 'm', 'mes', 'me', 'mo')
            ->join('o.orderMeals', 'om')
            ->join('om.meal', 'm')
            ->leftJoin('om.orderMealExtraIngredients', 'mes')
            ->leftJoin('mes.mealExtraIngredient', 'me')
            ->leftJoin('om.mealOption', 'mo')
            ->leftJoin('o.user', 'ou')
            ->where('o.id = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param \Nmotion\NmotionBundle\Entity\Order $order
     * @param int $status
     */
    public function setOrderStatus(Entity\Order $order, $status)
    {
        $statusRepository = $this->_em->getRepository('NmotionNmotionBundle:OrderStatus');

        /** @var Entity\OrderStatus $orderStatus */
        $orderStatus = $statusRepository->find($status);

        $slaves = $order->getSlaves();
        if (! empty($slaves)) {
            $unlinkMaster = false;
            $slaveStatus  = $orderStatus;
            if ($status == Entity\OrderStatus::PAID) {
                $slaveStatus = $statusRepository->find(Entity\OrderStatus::PAID);
            } elseif (in_array($status, [Entity\OrderStatus::CANCELLED, Entity\OrderStatus::FAILED])) {
                $unlinkMaster = true;
                $slaveStatus  = $statusRepository->find(Entity\OrderStatus::NEW_ORDER);
            }

            /** @var Entity\Order $slave */
            foreach ($slaves as $slave) {
                if ($unlinkMaster) {
                    $slave->setMaster(null);
                }
                $slave->setOrderStatus($slaveStatus);
                $this->_em->persist($slave);
            }
        }

        $order->setOrderStatus($orderStatus);
        $this->_em->persist($order);

        $this->_em->flush();
    }

    /**
     * @param $id Order.id
     *
     * @return Order|null
     */
    public function getById($id)
    {
        $order = $this->find($id);

        if (!$order instanceof Order) {
            $order = null;
        }

        return $order;
    }

    /**
     * Return total number of orders
     *
     * @return int
     */
    public function getTotalOrders()
    {
        return (int) $this->_em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('NmotionNmotionBundle:Order', 'r')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns total number of orders for particular period.
     *
     * @param int|DateTime $dateFrom Either timestamp or DateTime instance
     * @param int|DateTime $dateTo   Either timestamp or DateTime instance
     *
     * @return array
     */
    public function getTotalForPeriod($dateFrom, $dateTo)
    {
        $dateFrom = $dateFrom instanceof DateTime ? $dateFrom->getTimestamp() : $dateFrom;
        $dateTo   = $dateTo instanceof DateTime ? $dateTo->getTimestamp() : $dateTo;

        return (int) $this->_em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('NmotionNmotionBundle:Order', 'r')
            ->where('r.updatedAt BETWEEN ?0 AND ?1')
            ->setParameters([$dateFrom, $dateTo])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
