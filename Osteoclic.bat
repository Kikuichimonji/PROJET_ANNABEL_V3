@echo off
REM Laragon/symfony CLI ne sont plus necessaires : le serveur PHP integre suffit.
REM Toute la logique de lancement (sans fenetre console, ouverture navigateur) est dans le .vbs.
start "" wscript.exe "%~dp0Osteoclic.vbs"
