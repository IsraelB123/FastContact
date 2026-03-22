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
                echo 'Reiniciando contenedores de FastContact...'
                // Cambiamos el espacio por un guion para usar el binario de docker-compose
                sh 'docker-compose down --remove-orphans && docker-compose up -d'
            }
        }
    }
}