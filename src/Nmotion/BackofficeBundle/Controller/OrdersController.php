<?php

namespace Nmotion\BackofficeBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Nmotion\NmotionBundle\Entity\Order;
use Nmotion\NmotionBundle\Entity\Repositories\OrderRepository;

class OrdersController extends BackofficeController
{
    use RestaurantAssertAccess;

    /**
     * GET /orders
     *
     * @throws AccessDeniedException
     * @return Response json
     */
    public function getOrdersAction()
    {
        $this->setSerializerGroups(['backoffice', 'backoffice.list']);

        if (! $this->get('security.context')->isGranted('ROLE_SOLUTION_ADMIN')) {
            throw new AccessDeniedException;
        }

        /** @var $repository OrderRepository */
        $repository = $this->getDoctrine()->getRepository('NmotionNmotionBundle:Order');

        $orderBy = null;
        if ($this->getRequest()->get('sort')) {
            $orderBy = [
                $this->getRequest()->get('sort') => ($this->getRequest()->get('order') === 'DESC' ? 'DESC' : 'ASC')
            ];
        }

        $filters = $this->getRequestFilters();
        $start   = $this->getRequest()->get('start', null);
        $limit   = $this->getRequest()->get('limit', null);

        if (array_key_exists('dateFrom', $filters) && array_key_exists('dateTo', $filters)) {
            $dateFrom = new \DateTime($filters['dateFrom']);
            $dateTo   = new \DateTime($filters['dateTo']);
            $dateTo->add(new \DateInterval('PT23H59M59S'));
            $total    = $repository->getTotalForPeriod($dateFrom, $dateTo);
            $orders   = $repository->findForPeriod($dateFrom, $dateTo, $orderBy, $limit, $start);
        } else {
            $total  = $repository->getTotalOrders();
            $orders = $repository->findBy([], $orderBy, $limit, $start);
        }

        return $this->entriesResponse($orders, $total);
    }

    /**
     * GET /orders/1;5;8;9;12
     *
     * @param string $ids
     *
     * @return Response json
     *
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     */
    public function getOrderAction($ids)
    {
        $this->setSerializerGroups(['backoffice', 'backoffice.entity']);

        $idsArray = explode(';', $ids);

        $this->assertUserHasAccessToOrders($ids);

        /** @var $repository OrderRepository */
        $repository = $this->getDoctrine()->getRepository('NmotionNmotionBundle:Order');

        $total = 0;
        $orders = [];
        foreach ($idsArray as $id) {
            $order = $repository->find((int)$id);
            if (!$order instanceof Order) {
                throw new NotFoundHttpException('Order not found with id: ' . $id);
            }
            $orders[] = $order;
            $total++;
        }

        return $this->entriesResponse($orders, $total);
    }
}
