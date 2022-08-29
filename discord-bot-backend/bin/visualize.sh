#!/usr/bin/env bash

# >>> conda initialize >>>
# !! Contents within this block are managed by 'conda init' !!
__conda_setup="$('/home/ubuntu/miniconda3/bin/conda' 'shell.bash' 'hook' 2> /dev/null)"
if [ $? -eq 0 ]; then
    eval "$__conda_setup"
else
    if [ -f "/home/ubuntu/miniconda3/etc/profile.d/conda.sh" ]; then
        . "/home/ubuntu/miniconda3/etc/profile.d/conda.sh"
    else
        export PATH="/home/ubuntu/miniconda3/bin:$PATH"
    fi
fi
unset __conda_setup
# <<< conda initialize <<<

cd /home/ubuntu/stable-diffusion

conda activate ldm

python scripts/txt2img.py \
  --prompt "$1" \
  --plms \
  --ckpt sd-v1-4.ckpt \
  --skip_grid \
  --n_samples 1 \
  --n_iter 1 \
  --seed $2 \
  --outdir="/var/tmp/$3"
