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
                    
                    # 2. Descargamos Docker Compose (¡Esto es lo nuevo!)
                    curl -L "https://github.com/docker/compose/releases/download/v2.29.7/docker-compose-linux-x86_64" -o /usr/local/bin/docker-compose
                    chmod +x /usr/local/bin/docker-compose
                '''
            }
        }
        stage('Despliegue') {
            steps {
                echo 'Iniciando despliegue selectivo...'
                
                // NOTA: Ahora usamos docker-compose (con guion)
                sh '/usr/local/bin/docker-compose up -d --no-recreate db'
                sh '/usr/local/bin/docker-compose up -d --force-recreate app'
                
                echo 'Reparando permisos de la aplicación...'
                sh '/usr/local/bin/docker exec -u root fc_app chmod -R 777 /var/www/html /tmp'
            }
        }
    }
}