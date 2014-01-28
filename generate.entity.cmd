@echo off
IF NOT [%1] == [] (
    set ENTITY_NAME=%1
) ELSE (
    echo Enter entity name ^(e.g. Restaurant^):
    set /p ENTITY_NAME=
)
php ./app/console --env=local doctrine:generate:entities --path=src --no-backup NmotionNmotionBundle:%ENTITY_NAME% && pause