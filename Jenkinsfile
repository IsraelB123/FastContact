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
        // Usamos /usr/bin/docker-compose para asegurar que lo encuentre
        sh '/usr/bin/docker-compose up -d --force-recreate db app'
    }
}
    }
}