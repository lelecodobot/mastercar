@echo off
echo ============================
echo   MASTER CAR AUTO GIT PUSH
echo ============================

cd /d C:\xampp\htdocs\mastercar

echo.
echo Adicionando arquivos...
git add .

echo.
set /p msg="Mensagem do commit: "

git commit -m "%msg%"

echo.
echo Enviando para GitHub...
git push origin main

echo.
echo FINALIZADO
pause
