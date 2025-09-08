#!/bin/bash

# Script para preparar el repositorio para GitHub

echo "=== Preparando AgendaFlow para GitHub ==="
echo ""

# Inicializar git si no existe
if [ ! -d ".git" ]; then
    echo "→ Inicializando repositorio git..."
    git init
fi

# Agregar archivos al staging
echo "→ Agregando archivos al staging..."
git add .

# Mostrar estado
echo ""
echo "→ Estado del repositorio:"
git status

echo ""
echo "=== Preparación completada ==="
echo ""
echo "Próximos pasos:"
echo "1. git commit -m 'Initial commit: AgendaFlow - Sistema de Gestión de Turnos'"
echo "2. git branch -M main"
echo "3. git remote add origin https://github.com/TU_USUARIO/agendaflow.git"
echo "4. git push -u origin main"
echo ""
echo "Nota: Recuerda cambiar TU_USUARIO por tu usuario de GitHub"