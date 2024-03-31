FROM ubuntu:22.04

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y php8.1 php8.1-mysql \ 
    && rm -rf /var/lib/apt/lists/*

WORKDIR /scripts
