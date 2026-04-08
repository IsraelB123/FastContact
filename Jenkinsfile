pipeline {
    agent any
    stages {
        stage('Instalar Herramientas') {
            steps {
                echo 'Instalando cliente de Docker v27.3.1...'
                sh '''
                    apt-get update && apt-get install -y curl
                    # Descargamos la versión 27.3.1 para cumplir con la API 1.44+
                    curl -fsSL https://download.docker.com/linux/static/stable/x86_64/docker-27.3.1.tgz | tar -xzC /tmp
                    mv /tmp/docker/docker /usr/local/bin/docker
                    chmod +x /usr/local/bin/docker
                '''
            }
        }
        stage('Despliegue') {
            steps {
                echo 'Limpiando y Desplegando...'
                // El socket ya debería reconocer esta versión
                sh '/usr/local/bin/docker rm -f fc_app fc_db || true'
                
                // Usamos --force-recreate para asegurar que no haya conflictos de nombre
                sh '/usr/local/bin/docker compose up -d --force-recreate db app'
                
                sh '/usr/local/bin/docker exec -u root fc_app chmod -R 777 /var/www/html /tmp'
            }
        }
    }
}