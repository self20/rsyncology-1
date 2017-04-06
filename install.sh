#!/bin/bash
#
# Rsyncology
# Auteur: Script rsync par 4r3, adapté par Jedediah
# 		  Adapté par Sunnay
#
# Installation :
# cd /tmp
# git clone https://github.com/4r3/synchro-seedbox
# cd rsyncology
# chmod +x makeinstall.sh
#./makeinstall.sh
#

# variables couleurs
CSI="\033["
CEND="${CSI}0m"
CRED="${CSI}1;31m"
CGREEN="${CSI}1;32m"
CYELLOW="${CSI}1;33m"
CBLUE="${CSI}1;34m"

clear

# contrôle droits utilisateur
if [ $(id -u) -ne 0 ]
	then
		echo ""
		echo -e "${CRED}Ce script doit être exécuté en root.$CEND" 1>&2
		echo ""
		exit 1
	fi

# logo
echo ""
echo -e "${CBLUE}                                Synchro-Seedbox$CEND"
echo ""
echo -e "${CYELLOW}          Ce script va vous permettre d'installer une synchronisation$CEND"
echo -e "${CYELLOW}            automatique entre votre serveur dédié et votre seedbox.$CEND"
echo ""
echo -e "${CBLUE}        Script rsync par 4r3, script d'installation et php par Jedediah.$CEND"
echo -e "${CBLUE}              Gros merci à ex_rat et à la communauté mondedie.fr !$CEND"
echo -e "${CBLUE}              PHP & Script adapté par Sunnay !$CEND"
echo ""

#Récupération des informations utilisateur
echo ""
echo -e "${CGREEN}Entrer votre nom d'utilisateur:$CEND"
read USER
echo ""

echo -e "${CGREEN}Entrer le dossier à surveiller sur le serveur\n:$CEND"
read FOLDER
echo ""

echo -e "${CGREEN}Entrer l'utilisateur SSH du NAS:$CEND"
read NASUSER
echo ""

echo -e "${CGREEN}Entrer l'adresse de votre NAS:$CEND"
read NASADDR
echo ""

echo -e "${CGREEN}Entrer le port SSH de votre NAS:$CEND"
read NASPORT
echo ""

echo -e "${CGREEN}Entrer le dossier de synchro sur le NAS\n(/volumeX/dossier sur NAS Synology):$CEND"
read NASFOLDER
echo ""

echo -e "${CGREEN}Entrer le répertoire d'installation de la page web\n(/var/www/rsyncology):$CEND"
read FOLDERWEB
echo ""

#Création de l'arborescence du script
mkdir -p /home/$USER/script/rsync
cp -R scripts/* /home/$USER/script/rsync

#Création de l'arborescence de la page web
mkdir -p $FOLDERWEB
cp -R web/* $FOLDERWEB
mkdir $FOLDERWEB/conf

#Ecriture des variables dans le fichier de configuration
sed -i "s/@user@/$USER/g;" /home/$USER/script/rsync/conf/user.cfg
sed -i 's#@media_folder@#'$FOLDER'#' /home/$USER/script/rsync/conf/user.cfg
sed -i "s/@nas_user@/$NASUSER/g;" /home/$USER/script/rsync/conf/user.cfg
sed -i "s/@nas_ip@/$NASADDR/g;" /home/$USER/script/rsync/conf/user.cfg
sed -i "s/@nas_port@/$NASPORT/g;" /home/$USER/script/rsync/conf/user.cfg
sed -i 's#@nas_folder@#'$NASFOLDER'#' /home/$USER/script/rsync/conf/user.cfg

chmod +x /home/$USER/script/rsync/synchro.sh
chown -R $USER:$USER /home/$USER/script/rsync/synchro.sh

#Creation des liens symboliques vers le dossier web.
ln -s /home/$USER/script/rsync/logs $FOLDERWEB/logs

chown -R www-data:www-data $FOLDERWEB

#write out current crontab
crontab -l > mycron
#echo new cron into cron file
echo "* * * * * cd /home/$USER/script/rsync/synchro && ./synchro.sh > /dev/null" >> mycron
#install new cron file
crontab mycron
rm mycron

#Suppression des fichiers d'installation
rm -R /tmp/atomicbox

echo -e "${CBLUE}Installation terminée.$CEND"

echo ""
