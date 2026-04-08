pipeline {
    agent any
    stages {
        stage('Instalar Herramientas') {
            steps {
                echo 'Instalando cliente de Docker en Jenkins...'
                sh '''
                    apt-get update && apt-get install -y lsb-release curl gpg
                    curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
                    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
                    apt-get update && apt-get install -y docker-ce-cli
                '''
            }
        }
        stage('Despliegue') {
            steps {
                echo 'Desplegando con el nuevo cliente...'
                // Usamos comandos directos, el socket ya está conectado
                sh 'docker rm -f fc_app fc_db || true'
                sh 'docker compose up -d db app'
                sh 'docker exec -u root fc_app chmod -R 777 /var/www/html /tmp'
            }
        }
    }
}