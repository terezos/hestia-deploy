FROM webdevops/php-apache-dev:8.4

WORKDIR /app

# Install essential packages
RUN apt-get update && apt-get install -y \
    openssh-client \
    git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Sendmail config
RUN curl -sSL https://github.com/BlueBambooStudios/mhsendmail/releases/download/v0.3.0/mhsendmail_linux_arm -o mhsendmail \
    && chmod +x mhsendmail \
    && mv mhsendmail /usr/local/bin/mhsendmail

# PHP CS Fixer
#RUN composer global require friendsofphp/php-cs-fixer
#ENV PATH="/root/.composer/vendor/bin:$PATH"


EXPOSE 80 443
