pipeline {
    agent any
    stages {
        stage('Instalar Herramientas') {
            steps {
                echo 'Instalando cliente de Docker y Docker Compose...'
                sh '''
                    apt-get update && apt-get install -y curl
                    
                    # 1. Descargamos el binario de Docker
                    curl -fsSL https://download.docker.com/linux/static/stable/x86_64/docker-27.3.1.tgz | tar -xzC /tmp
                    mv /tmp/docker/docker /usr/local/bin/docker
                    chmod +x /usr/local/bin/docker
                    
                    # 2. Descargamos Docker Compose
                    curl -L "https://github.com/docker/compose/releases/download/v2.29.7/docker-compose-linux-x86_64" -o /usr/local/bin/docker-compose
                    chmod +x /usr/local/bin/docker-compose
                '''
            }
        }
        
        // ¡LA ETAPA DE TEST VA AQUÍ ANTES DE DESPLEGAR!
        stage('Test') {
            steps {
                echo 'Ejecutando pruebas automáticas de PHP...'
                // Usamos la ruta absoluta de Windows para que Docker Desktop encuentre el test.php
                sh '/usr/local/bin/docker run --rm -v "C:/Users/Israel/Desktop/fastcontact/jenkins_home/workspace/FastContact-Pipeline":/app php:8.0-cli php /app/test.php'
            }
        }

        stage('Despliegue') {
            steps {
                echo 'Iniciando despliegue selectivo...'
                
                sh '/usr/local/bin/docker-compose up -d --no-recreate db'
                sh '/usr/local/bin/docker-compose up -d --force-recreate app'
                
                echo 'Reparando permisos de la aplicación...'
                sh '/usr/local/bin/docker exec -u root fc_app chmod -R 777 /var/www/html /tmp'
            }
        }
    }
}