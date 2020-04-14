pipeline {
    agent {
        label 'builder'
    }
    stages {
        stage('Tests') {
            agent {
                docker {
                    image 'alexwijn/docker-git-php-composer'
                    reuseNode true
                }
            }
            environment {
                HOME = '.'
            }
            options {
                skipDefaultCheckout()
            }
            steps {
                withCredentials([string(credentialsId: 'jenkins_github_token', variable: 'GIT_TOKEN')]) {
                    sh(
                        label: 'Install/Update sources from Composer',
                        script: "COMPOSER_AUTH='{\"github-oauth\": {\"github.com\": \"$GIT_TOKEN\"}}\' composer install --no-interaction --no-ansi --no-progress"
                    )
                }
                script {
                    deps = sh(returnStdout: true, script: 'php -n index.php oat\\taoDevTools\\scripts\\tools\\DepsInfo -e taoDevTools').trim()
                    deps = deps.substring(deps.indexOf('\n')+1);
                    def propsJson = readJSON text: deps
                    missedDeps = propsJson['taoDevTools']['missed'].toString()
                    try {
                        assert missedDeps == "[]"
                    } catch(Throwable t) {
                        error("Missed dependencies found: $missedDeps")
                    }
                }
            }
        }
    }
}