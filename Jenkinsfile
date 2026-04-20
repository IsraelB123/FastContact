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
                // Actualizamos la ruta al nuevo formato de WSL apuntando a Documentos
                sh '/usr/local/bin/docker run --rm -v /mnt/c/Users/Israel/Documents/fastcontact/jenkins_home/workspace/FastContact-Pipeline:/app php:8.0-cli php /app/test.php'
            }
        }

        stage('Seguridad (DevSecOps)') {
            steps {
                echo 'Ejecutando escaneo de vulnerabilidades con Trivy...'
                // Usamos la versión segura anclada (0.69.3) para evitar caídas por la etiqueta 'latest'
                sh '/usr/local/bin/docker run --rm aquasec/trivy:0.69.3 image --severity HIGH,CRITICAL php:8.0-apache'
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