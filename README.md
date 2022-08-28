AWS Account ID: 466465217292
Root account root user: kiessling.manuel+aws-stable-diffusion-root@gmail.com


Ubuntu 22.04:

https://www.linuxcapable.com/how-to-install-nvidia-drivers-on-ubuntu-22-04-lts/

sudo apt-get install ubuntu-drivers-common
ubuntu-drivers devices
sudo ubuntu-drivers autoinstall
- reboot

sudo apt-get update
sudo apt-get -u dist-upgrade
sudo apt-get install libsm6 libxext6 libxrender-dev

wget https://repo.anaconda.com/miniconda/Miniconda3-py38_4.12.0-Linux-x86_64.sh

bash Miniconda3-py38_4.12.0-Linux-x86_64.sh
- inititalize: yes
- log out / log in

git clone https://github.com/CompVis/stable-diffusion.git
cd stable-diffusion/
conda env create -f environment.yaml
conda activate ldm




Discord Bot Token:
MTAxMzM3MzA0MzYyNTcwNTUxMw.Gy5jK6.ApVUkSGi9Y51z3cne5BV-sgLOoXuSFpb388FY0

Discord Bot OAuth2 URL:
https://discord.com/api/oauth2/authorize?client_id=1013373043625705513&permissions=2147534848&scope=bot
