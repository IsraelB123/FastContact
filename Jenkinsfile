pipeline {
    agent any
    
    stages {
        stage('Descarga de Código') {
            steps {
                echo 'Obteniendo la última versión de FastContact...'
            }
        }
        stage('Despliegue con Docker') {
            steps {
                echo 'Desplegando FastContact y reparando permisos...'
                // Cambiamos 'docker-compose' por 'docker compose'
                sh 'docker compose up -d --force-recreate db app'
                
                // Reparamos permisos
                sh 'docker compose up -d db app'
                sh 'docker exec -u root fc_app chmod -R 755 /var/www/html'
            }
        }
    }
}