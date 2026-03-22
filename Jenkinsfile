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
                echo 'Reiniciando servicios de FastContact (App y DB)...'
                // Solo recreamos 'db' y 'app' para que Jenkins siga vivo
                sh 'docker-compose up -d --force-recreate db app'
            }
        }
    }
}