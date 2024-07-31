#!/bin/bash

# Set environment variables
export AWS_ACCESS_KEY_ID="AKIA3Z6QG7OJFIWYPKUI"
export AWS_SECRET_ACCESS_KEY="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"
export AWS_REGION="eu-west-2"

ECR_REGISTRY="811648613266.dkr.ecr.eu-west-2.amazonaws.com"
IMAGE_NAME="arbitrage-bot"
TAG="latest"


# Delete log files older than 2 minutes
find /home/tmwcl/Deployment/ -name "pull_latest_image_*.log" -type f -mmin +60 -exec echo "Deleting {}" \; -exec rm {} \;
# Get current date and time
CURRENT_DATE=$(date +"%Y-%m-%d_%H-%M-%S")

# Define log file path
LOG_FILE="/home/tmwcl/Deployment/Logs/pull_latest_image_${CURRENT_DATE}.log"

# Redirect stdout and stderr to log file
exec > >(tee -a ${LOG_FILE}) 2>&1




# Login to ECR
aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REGISTRY

# # Pull the latest image
# docker pull $ECR_REGISTRY/$IMAGE_NAME:$TAG

#  check if the latest image is more recent than the current image
docker pull $ECR_REGISTRY/$IMAGE_NAME:$TAG

#  check if there is a container running with the name 'deployment_arbitrage-bot_1' if not set tmp file to '0'
docker ps -a | grep deployment_arbitrage-bot_1

docker inspect --format='{{.Created}}' $ECR_REGISTRY/$IMAGE_NAME:$TAG > /tmp/latest_image_created

if [ $? -eq 1 ]; then
    echo "No container running with the name 'deployment_arbitrage-bot_1'"
    echo "0" > /tmp/current_image_created
else
    echo "Container running with the name 'deployment_arbitrage-bot_1'"
    #  grab the current image being used by deployment_arbitrage-bot_1
    current_image=$(docker inspect --format='{{.Image}}' deployment_arbitrage-bot_1)

    #  grab the creation date of the current image
    docker inspect --format='{{.Created}}' $current_image > /tmp/current_image_created
fi



# echo the 2 dates to the console
echo "Latest image created: $(cat /tmp/latest_image_created)"
echo "Current image created: $(cat /tmp/current_image_created)"


diff /tmp/latest_image_created /tmp/current_image_created

if [ $? -eq 1 ]; then
    echo "The latest image is more recent than the current image"

    #  check if docker container exists
    docker ps -a | grep deployment_arbitrage-bot_1

    # if container exists, stop and remove it
    if [ $? -eq 0 ]; then
        docker stop deployment_arbitrage-bot_1
        docker rm deployment_arbitrage-bot_1
    fi

    # check if volume deployment_sail-laravel exists if so delete it
    docker volume ls | grep deployment_sail-laravel

    if [ $? -eq 0 ]; then
        docker volume rm deployment_sail-laravel
    fi

    docker-compose -f ~/Deployment/docker-compose.yml --env-file ~/Deployment/.env up -d

    # wait for the container to start then run cmd 'npm run build'
    # sleep 20


    # docker exec -it deployment_arbitrage-bot_1 npm run build

else
    echo "The latest image is not more recent than the current image"
    exit 0
fi
