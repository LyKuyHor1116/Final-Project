@echo off

:loop

git pull

timeout /t 5 >nul

goto loop