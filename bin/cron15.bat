@echo off
:repeat
c:\wamp\bin\php\php5.3.5\php.exe c:\wamp\www\cron\cron.php
echo -------------------------------------------------------------------------------
choice /t 15 /d:N > NUL
goto repeat