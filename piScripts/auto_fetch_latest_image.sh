#!/bin/bash

# Set environment variables these should be requested through the client communication container
export AWS_ACCESS_KEY_ID=""
export AWS_SECRET_ACCESS_KEY=""
export AWS_REGION="eu-west-2"

ECR_REGISTRY=""

# prune dangling images
docker image prune -f

# Arrays of image names and container names
IMAGE_NAMES=("arbitrage-bot")
CONTAINER_NAMES=("deployment-arbitrage-bot-1")
VOLUME_NAMES=("deployment_sail-laravel")

# Delete log files older than 60 minutes
find /home/las/Deployment/ -name "pull_latest_image_*.log" -type f -mmin +60 -exec echo "Deleting {}" \; -exec rm {} \;

# Get current date and time
CURRENT_DATE=$(date +"%Y-%m-%d_%H-%M-%S")

# Define log file path
LOG_FILE="/home/las/Deployment/Logs/pull_latest_image_${CURRENT_DATE}.log"

# Create log file
touch ${LOG_FILE}

# Redirect stdout and stderr to log file
exec > >(tee -a ${LOG_FILE}) 2>&1

# Login to ECR
aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REGISTRY

# Loop through the images and containers
for i in "${!IMAGE_NAMES[@]}"; do
    IMAGE_NAME="${IMAGE_NAMES[$i]}"
    CONTAINER_NAME="${CONTAINER_NAMES[$i]}"
    VOLUME_NAME="${VOLUME_NAMES[$i]}"
    TAG="latest"

    echo "Processing $IMAGE_NAME with container $CONTAINER_NAME"

    # Login to ECR
    aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REGISTRY


    # Pull the latest image
    docker pull $ECR_REGISTRY/$IMAGE_NAME:$TAG

    # Get the creation date of the latest image
    docker inspect --format='{{.Created}}' $ECR_REGISTRY/$IMAGE_NAME:$TAG > /tmp/latest_image_created_$i

    # Check if the container is running
    docker ps -a | grep $CONTAINER_NAME

    if [ $? -eq 1 ]; then
        echo "No container running with the name '$CONTAINER_NAME'"
        echo "0" > /tmp/current_image_created_$i
    else
        echo "Container running with the name '$CONTAINER_NAME'"
        # Get the current image used by the container
        current_image=$(docker inspect --format='{{.Image}}' $CONTAINER_NAME)

        # Get the creation date of the current image
        docker inspect --format='{{.Created}}' $current_image > /tmp/current_image_created_$i
    fi

    # Compare the creation dates
    echo "Latest image created: $(cat /tmp/latest_image_created_$i)"
    echo "Current image created: $(cat /tmp/current_image_created_$i)"

    diff /tmp/latest_image_created_$i /tmp/current_image_created_$i

    if [ $? -eq 1 ]; then
        echo "The latest image is more recent than the current image"

        # Check if the container exists, stop and remove it if it does
        docker ps -a | grep $CONTAINER_NAME

        if [ $? -eq 0 ]; then
            docker stop $CONTAINER_NAME
            docker rm $CONTAINER_NAME
        fi

        # Check if the volume exists, delete it if it does
        docker volume ls | grep $VOLUME_NAME

        if [ $? -eq 0 ]; then
            docker stop $CONTAINER_NAME
            docker rm $CONTAINER_NAME
            docker volume rm $VOLUME_NAME
        fi


        # Optionally, wait for the container to start then run npm build (if needed)
        # sleep 20
        # docker exec -it $CONTAINER_NAME npm run build

    else
        echo "The latest image is not more recent than the current image for $CONTAINER_NAME"
    fi



    # Clean up temporary files
    rm /tmp/latest_image_created_$i /tmp/current_image_created_$i
done

docker compose -f ~/Deployment/docker-compose.yml --env-file ~/Deployment/.env up -d

# docker image prune -a -f

exit 0
