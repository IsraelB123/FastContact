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
                // Usamos la ruta absoluta para que no haya pierde
                sh 'docker compose down && docker compose up -d'
            }
        }
    }
}