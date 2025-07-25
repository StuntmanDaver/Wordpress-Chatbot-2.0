@echo off
:: Gary AI Plugin - Git Configuration Setup
:: Helps configure Git for GitHub if not already set up

setlocal enabledelayedexpansion

:: Color codes
set "GREEN=[92m"
set "YELLOW=[93m"
set "RED=[91m"
set "BLUE=[94m"
set "MAGENTA=[95m"
set "RESET=[0m"

echo %MAGENTA%=================================================%RESET%
echo %MAGENTA% Gary AI Plugin - Git Setup Helper%RESET%
echo %MAGENTA%=================================================%RESET%

echo %BLUE%Checking Git configuration...%RESET%
echo.

:: Check if Git is installed
git --version >nul 2>&1
if errorlevel 1 (
    echo %RED%Git is not installed or not in PATH!%RESET%
    echo %YELLOW%Please install Git from: https://git-scm.com/download/win%RESET%
    goto :end
)

:: Check user.name
for /f "tokens=2 delims==" %%i in ('git config --global user.name 2^>nul') do set GIT_NAME=%%i
if not defined GIT_NAME (
    echo %YELLOW%Git user.name not set%RESET%
    set /p NEW_NAME="Enter your full name: "
    git config --global user.name "!NEW_NAME!"
    echo %GREEN%Set user.name to: !NEW_NAME!%RESET%
) else (
    echo %GREEN%Git user.name: %GIT_NAME%%RESET%
)

:: Check user.email
for /f "tokens=2 delims==" %%i in ('git config --global user.email 2^>nul') do set GIT_EMAIL=%%i
if not defined GIT_EMAIL (
    echo %YELLOW%Git user.email not set%RESET%
    set /p NEW_EMAIL="Enter your GitHub email: "
    git config --global user.email "!NEW_EMAIL!"
    echo %GREEN%Set user.email to: !NEW_EMAIL!%RESET%
) else (
    echo %GREEN%Git user.email: %GIT_EMAIL%%RESET%
)

echo.
echo %BLUE%Current Git configuration:%RESET%
git config --global --list | findstr user

echo.
echo %BLUE%Checking repository status...%RESET%

:: Check if we're in a Git repository
if not exist ".git" (
    echo %YELLOW%Not in a Git repository%RESET%
    set /p INIT_REPO="Initialize Git repository here? (y/N): "
    if /i "!INIT_REPO!"=="y" (
        git init
        echo %GREEN%Git repository initialized!%RESET%
    )
    goto :end
)

:: Check remote origin
for /f "tokens=2 delims= " %%i in ('git remote get-url origin 2^>nul') do set REMOTE_URL=%%i
if not defined REMOTE_URL (
    echo %YELLOW%No remote origin set%RESET%
    echo %BLUE%To add GitHub remote, run:%RESET%
    echo   git remote add origin https://github.com/yourusername/your-repo.git
) else (
    echo %GREEN%Remote origin: %REMOTE_URL%%RESET%
)

:: Check current branch
for /f %%i in ('git rev-parse --abbrev-ref HEAD 2^>nul') do set CURRENT_BRANCH=%%i
if defined CURRENT_BRANCH (
    echo %GREEN%Current branch: %CURRENT_BRANCH%%RESET%
) else (
    echo %YELLOW%No commits yet%RESET%
)

echo.
echo %GREEN%Git setup check complete!%RESET%
echo.
echo %BLUE%Quick commands available:%RESET%
echo   %GREEN%git-quick.bat quick%RESET%   - Quick commit and push
echo   %GREEN%git-quick.bat status%RESET%  - Check status
echo   %GREEN%git-quick.bat sync%RESET%    - Pull latest changes
echo.

:end
pause 