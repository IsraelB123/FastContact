pipeline {
    agent any
    stages {
        stage('Instalar Herramientas') {
            steps {
                echo 'Instalando cliente de Docker de forma limpia...'
                sh '''
                    apt-get update && apt-get install -y curl
                    # Descargamos el binario estático (pesa poco y no requiere instalación)
                    curl -fsSL https://download.docker.com/linux/static/stable/x86_64/docker-24.0.7.tgz | tar -xzC /tmp
                    # Movemos el binario a una ruta que NO esté bloqueada
                    mv /tmp/docker/docker /usr/local/bin/docker
                    chmod +x /usr/local/bin/docker
                '''
            }
        }
        stage('Despliegue') {
            steps {
                echo 'Desplegando FastContact...'
                // Usamos la ruta completa para estar seguros
                sh '/usr/local/bin/docker rm -f fc_app fc_db || true'
                // Nota: docker-compose puede requerir instalación similar o usar 'docker compose'
                sh '/usr/local/bin/docker compose up -d db app'
                sh '/usr/local/bin/docker exec -u root fc_app chmod -R 777 /var/www/html /tmp'
            }
        }
    }
}