@echo off
setlocal

:: Enter port from command line or default to 8080
:: check-port.bat 8080 - check port 8080

set PORT=%1
if "%PORT%"=="" set PORT=8080

echo Checking port %PORT%...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :%PORT% ^| findstr LISTENING') do (
    set PID=%%a
)

if defined PID (
    echo Port %PORT% is in use by PID: %PID%
    tasklist /FI "PID eq %PID%"
) else (
    echo Port %PORT% is free.
)

endlocal
pause
