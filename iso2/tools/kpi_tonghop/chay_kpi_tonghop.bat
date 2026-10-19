@echo off
chcp 65001 >nul
cd /d "%~dp0"
python -c "import openpyxl" 2>nul || pip install openpyxl
start "" pythonw kpi_tonghop.py
