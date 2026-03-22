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
                sh '/usr/bin/docker-compose up -d --force-recreate db app'
                // Esto arregla el Forbidden automáticamente
                sh 'docker exec -u root fc_app chown -R www-data:www-data /var/www/html'
                sh 'docker exec -u root fc_app chmod -R 755 /var/www/html'
            }
        }
    }
}