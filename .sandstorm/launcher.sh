mkdir -p /var/microapp
mkdir -p /var/microapp/pages
mkdir -p /var/microapp/data
mkdir -p /var/microapp/audios
mkdir -p /var/microapp/images
mkdir -p /var/microapp/videos
mkdir -p /var/www

if [ ! -L "/var/microapp/templates" ]; then
    ln -s /opt/app/templates /var/microapp/templates
fi

if [ ! -L "/var/microapp/assets" ]; then
    ln -s /opt/app/assets /var/microapp/assets
fi

if [ ! -f "/var/microapp/installok" ]; then
    rsync --recursive --ignore-existing /opt/app/pages/ /var/microapp/pages/
    rsync --recursive --ignore-existing /opt/app/data/ /var/microapp/data/
    rsync --recursive --ignore-existing /opt/app/audios/ /var/microapp/audio/
    rsync --recursive --ignore-existing /opt/app/images/ /var/microapp/images/
    rsync --recursive --ignore-existing /opt/app/videos/ /var/microapp/videos/
fi

if [ ! -D "/var/microapp/pages/HeaderContent" ]; then
    mkdir -p /var/microapp/pages/HeaderContent
    touch /var/microapp/pages/HeaderContent/index.html
fi

if [ ! -D "/var/microapp/pages/FooterContent" ]; then
    mkdir -p /var/microapp/pages/FooterContent
    touch /var/microapp/pages/FooterContent/index.html
fi

touch /var/microapp/installok
cd /var/microapp
exec /opt/app/microapp --data /var/microapp
exit 0
