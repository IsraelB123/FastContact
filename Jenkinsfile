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
                echo 'Limpiando y reiniciando servicios de FastContact...'
                // Primero bajamos los servicios específicos y luego los subimos
                sh '/usr/bin/docker-compose stop db app'
                sh '/usr/bin/docker-compose rm -f db app'
                sh '/usr/bin/docker-compose up -d --force-recreate db app'
            }
        }
    }
}