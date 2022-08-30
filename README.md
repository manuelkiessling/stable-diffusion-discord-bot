# A Discord Bot providing text2image capabilities using Stable Diffusion

Feel free to sponsor this project at https://patreon.com/manuelkiessling.

## Setup on Ubuntu 22.04

The following setup is known to work on AWS `g4dn.xlarge` instances, which feature a NVIDIA T4 GPU.

This installs the required OS packages:

    sudo apt-get update
    sudo apt-get -u dist-upgrade
    sudo apt-get install \
        ubuntu-drivers-common \
        libsm6 \
        libxext6 \
        libxrender-dev \
        mariadb-server \
        net-tools \
        php8.1-cli \
        php8.1-xml \
        php8.1-mysql \
        composer

This makes sure your kernel can talk to your NVIDIA GPU:

    ubuntu-drivers devices
    sudo ubuntu-drivers autoinstall
    sudo reboot

Running `nvidia-smi` should now look like this:

    +-----------------------------------------------------------------------------+
    | NVIDIA-SMI 515.65.01    Driver Version: 515.65.01    CUDA Version: 11.7     |
    |-------------------------------+----------------------+----------------------+
    | GPU  Name        Persistence-M| Bus-Id        Disp.A | Volatile Uncorr. ECC |
    | Fan  Temp  Perf  Pwr:Usage/Cap|         Memory-Usage | GPU-Util  Compute M. |
    |                               |                      |               MIG M. |
    |===============================+======================+======================|
    |   0  Tesla T4            Off  | 00000000:00:1E.0 Off |                    0 |
    | N/A   58C    P8    18W /  70W |      2MiB / 15360MiB |      0%      Default |
    |                               |                      |                  N/A |
    +-------------------------------+----------------------+----------------------+

This sets up the conda environment used by Stable Diffusion:

    wget https://repo.anaconda.com/miniconda/Miniconda3-py38_4.12.0-Linux-x86_64.sh
    bash Miniconda3-py38_4.12.0-Linux-x86_64.sh

When asked if you want to initialize stuff, answer `yes`.
Then, log out of your shell session and log back in - shell prompt should now start with `(base)`.

You can now set up Stable Diffusion:

    git clone https://github.com/CompVis/stable-diffusion.git
    cd stable-diffusion/
    conda env create -f environment.yaml
    conda activate ldm
    cd -

Important: Download [https://huggingface.co/CompVis/stable-diffusion-v-1-4-original/resolve/main/sd-v1-4.ckpt] to `~/stable-diffusion/sd-v1-4.ckpt`.

    sudo mysql
        GRANT ALL PRIVILEGES ON discord_bot_backend.* TO 'root'@'localhost' IDENTIFIED BY 'secret' ;

    git clone https://github.com/manuelkiessling/stable-diffusion-discord-bot.git
    cd stable-diffusion-discord-bot/discord-bot-backend
    cp .env.local.dist .env.local

Now, set up a new Discord application at [https://discord.com/developers/applications].

Edit file `.env.local` and set your own bot token - generate it at [https://discord.com/developers/applications/<your-application's-id>/bot].

    composer install
    php bin/console --no-debug doctrine:database:create
    php bin/console --no-debug doctrine:migrations:migrate

Start the following command once to have your bot register its `/draw` command with Discord:

    php bin/console --no-debug app:bot:register

Then, run the actual bot command in a separate screen session:

    screen
        php bin/console --no-debug app:bot:run
        CTRL-A D

And the task consumer in another one:

    screen
        while true; do php bin/console --no-debug messenger:consume async --memory-limit=2048M --time-limit=600 --limit 1 -vv; done
        CTRL-A D

Create an invite URL for your bot at [https://discord.com/developers/applications/<your-application's-id>/oauth2/url-generator]. You need scopes `bot` and `application.commands`, and permissions `Send Messages`, `Attach files`, `Read Message History`, and `Read Messages/View Channels`.

You then need to open the generated URL to invite the bot into your Discord server.

Talk to the bot using the `/draw` slash command.
