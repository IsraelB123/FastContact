pipeline {
    agent any
    stages {
        stage('Instalar Herramientas') {
            steps {
                echo 'Instalando cliente de Docker v27.3.1...'
                sh '''
                    apt-get update && apt-get install -y curl
                    # Descargamos el binario moderno para evitar errores de API
                    curl -fsSL https://download.docker.com/linux/static/stable/x86_64/docker-27.3.1.tgz | tar -xzC /tmp
                    mv /tmp/docker/docker /usr/local/bin/docker
                    chmod +x /usr/local/bin/docker
                '''
            }
        }
        stage('Despliegue') {
            steps {
                echo 'Iniciando despliegue selectivo...'
                
                // 1. Aseguramos que la DB esté arriba pero SIN recrearla (mantiene tus datos)
                sh '/usr/local/bin/docker compose up -d --no-recreate db'
                
                // 2. Recreamos solo la APP para aplicar tus cambios de PHP (config.php, login, etc)
                sh '/usr/local/bin/docker compose up -d --force-recreate app'
                
                echo 'Reparando permisos de la aplicación...'
                // Aplicamos permisos totales para evitar el error 'Forbidden'
                sh '/usr/local/bin/docker exec -u root fc_app chmod -R 777 /var/www/html /tmp'
            }
        }
    }
}