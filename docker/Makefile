CONTAINER_NAME=ghcr.io/fortrabbit/craft-copy

publish: build push

build:
	docker build . \
		--pull \
		--platform linux/amd64 \
		--build-arg CRAFT_IMAGE_TAG="7.2" \
		-t ${CONTAINER_NAME}:7.2
	docker build . \
		--pull \
		--platform linux/amd64 \
		--build-arg CRAFT_IMAGE_TAG="7.3" \
		-t ${CONTAINER_NAME}:7.3
	docker build . \
		--pull \
		--platform linux/amd64 \
		--build-arg CRAFT_IMAGE_TAG="7.4" \
		-t ${CONTAINER_NAME}:7.4
	docker build . \
		--pull \
		--platform linux/amd64 \
		--build-arg CRAFT_IMAGE_TAG="8.0" \
		-t ${CONTAINER_NAME}:8.0

push:
	docker push --all-tags docker.io/${CONTAINER_NAME}

pull:
	docker pull --all-tags docker.io/${CONTAINER_NAME}
