AWS Account ID: 466465217292
Root account root user: kiessling.manuel+aws-stable-diffusion-root@gmail.com


Ubuntu 22.04:

https://www.linuxcapable.com/how-to-install-nvidia-drivers-on-ubuntu-22-04-lts/

sudo apt-get install ubuntu-drivers-common libsm6 libxext6 libxrender-dev mariadb-server net-tools php8.1-cli php8.1-xml php8.1-mysql

ubuntu-drivers devices
sudo ubuntu-drivers autoinstall
- reboot

sudo apt-get update
sudo apt-get -u dist-upgrade

wget https://repo.anaconda.com/miniconda/Miniconda3-py38_4.12.0-Linux-x86_64.sh

bash Miniconda3-py38_4.12.0-Linux-x86_64.sh
- inititalize: yes
- log out / log in

git clone https://github.com/CompVis/stable-diffusion.git
cd stable-diffusion/
conda env create -f environment.yaml
conda activate ldm

cd discord-bot-backend
sudo mysql
    GRANT ALL PRIVILEGES ON discord_bot_backend.* TO 'root'@'localhost' IDENTIFIED BY 'secret' ;

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

screen
    php bin/console app:runbot
    CTRL-A D

screen
    php bin/console app:runbot
    CTRL-A D


Discord Bot Token:
MTAxMzM3MzA0MzYyNTcwNTUxMw.Gy5jK6.ApVUkSGi9Y51z3cne5BV-sgLOoXuSFpb388FY0

Discord Bot OAuth2 URL:
https://discord.com/api/oauth2/authorize?client_id=1013373043625705513&permissions=2147534848&scope=bot
