@echo off
setlocal

echo ==========================================
echo   Inalink - Mark Evans lokale AI setup
echo ==========================================
echo.

where ollama >nul 2>nul
if errorlevel 1 (
    echo [FOUT] Ollama is niet gevonden op deze computer.
    echo Installeer Ollama eerst vanaf de officiele Ollama website.
    echo Sluit daarna dit venster en voer setup-ollama.bat opnieuw uit.
    echo.
    pause
    exit /b 1
)

echo [OK] Ollama is gevonden.
echo.
echo Het snelle model qwen3:1.7b wordt gecontroleerd/gedownload...
echo Dit hoeft normaal alleen de eerste keer.
echo.

ollama pull qwen3:1.7b
if errorlevel 1 (
    echo.
    echo [FOUT] Het model kon niet worden gedownload.
    echo Controleer je internetverbinding en probeer opnieuw.
    pause
    exit /b 1
)

echo.
echo [KLAAR] qwen3:1.7b staat lokaal op je pc.
echo Inalink kan Mark nu gratis via Ollama laten antwoorden.
echo.
echo Zorg dat config.local.php ai_provider = ollama gebruikt.
echo.
pause
endlocal
