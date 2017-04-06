	#!/bin/bash
# Script de synchronisation automatique via rsync d'un repertoire local avec un repertoire distant
# Sens de transfert Local --> Distant

# Fonction de listage des fichiers a envoyer.
# Les fichiers sont écrits dans $LIST

function parcours_rep
{
	local rep
	local rel
	local age
	local i

	if test $# -lt 1
	then
	rep=$DIR
	rel=""
	else
	rel="$1/"
	rep="$DIR$rel"
	fi

	echo "dans $rep"

	for i in "$rep"*
	do
		if test -f "$i"
		then
			# Age in epoch
			age=`stat -c %Y "$i"`
			echo "$age:${i//"$DIR/"/}" >> $LIST
		else
		if test -d "$i"
		then
			parcours_rep "${i//"$DIR"/}"
		fi
		fi
	done
}
# Supression et recreation de $LIST
function maj_liste
{
	rm $LIST 2> /dev/null
	parcours_rep
}
# Parcours de la liste des fichiers a envoyer et lancement de la fonction d'envoi
function envois_fichiers
{
	patern='*[0-9]:'
	patern2='/*'

	old_IFS=$IFS
	IFS=$'\n'
	for line in $(sort $LIST)
	do
		IFS=$old_IFS
		line1=${line##$patern}
		fic=${line1##"$DIR"}
		rel="${fic%$patern2}/"
		envois_fichier "$line1"
		maj_liste
	done
	IFS=$old_IFS
}
# Envoi du fichier courrant via rsync
function envois_fichier
{
	echo "$DIR/$1" > $LOG
	rsync -aPRL -e "ssh " $ARGS "$1" "$user_SSH"@"$IP":"$dest_NAS" >> $LOG
	cp $LOG $HISTORY_LOG.$(date +%s)
	# Rajout des droits a l'utilisateur web pour suppression des logs après import dans l'historique.
	chown sunnay:www-data $HISTORY_LOG.$(date +%s)
	chmod g+w $HISTORY_LOG.$(date +%s)
}

# Chargement des paramètres utilisateur et definition des fichiers de logs
. ./conf/user.cfg


# MAIN
if test -f /tmp/synchro
then
	maj_liste
	echo -e "err 3:script deja en execution"
	exit 3
else
	touch /tmp/synchro
fi

cd $DIR
maj_liste

while test -f $LIST
do
	envois_fichiers
done

# Cleaning
rm /tmp/synchro
echo "Pas de fichier à envoyer." > $LOG
echo "0 N/A" >> $LOG
