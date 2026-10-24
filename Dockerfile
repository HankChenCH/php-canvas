# PHP 8.4 环境构建并验证本类库（GD + Imagick 双驱动，与 CI 的 Imagick 路径一致）
FROM php:8.4-cli

# 系统依赖：
# - freetype/jpeg/png/onig：编译 GD（带 freetype）与 mbstring 所需
# - libmagickwand-dev：pecl 安装 imagick 所需
# - fonts-dejavu-core：CanvasTestCase::systemTtf() 依赖
#   /usr/share/fonts/truetype/dejavu/DejaVuSans.ttf，Imagick 驱动渲染文字必须有真实字体文件
RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libonig-dev \
        libmagickwand-dev \
        fonts-dejavu-core \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# mbstring 为 intervention/image 必需；gd 与 imagick 提供双驱动
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd mbstring \
    && pecl install imagick \
    && docker-php-ext-enable imagick

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# 先只拷贝依赖清单再 composer install，充分利用层缓存
COPY composer.json composer.lock phpunit.xml.dist ./
RUN composer install --no-interaction --no-progress --prefer-dist

COPY src src
COPY tests tests
COPY scripts scripts

# 默认运行测试套件
CMD ["vendor/bin/phpunit"]
