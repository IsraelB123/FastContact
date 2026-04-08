pipeline {
    agent any 
    
    stages {
        stage('Limpieza Manual') {
            steps {
                echo 'Limpiando contenedores previos...'
                // Usamos comandos de shell que interactúan con el socket compartido
                sh 'docker rm -f fc_app fc_db || true'
            }
        }
        stage('Despliegue con Docker') {
            steps {
                echo 'Desplegando FastContact...'
                // Levantamos los servicios
                sh 'docker compose up -d'
                
                echo 'Reparando permisos...'
                sh 'docker exec -u root fc_app chown -R www-data:www-data /var/www/html'
                sh 'docker exec -u root fc_app chmod -R 755 /var/www/html'
            }
        }
    }
}