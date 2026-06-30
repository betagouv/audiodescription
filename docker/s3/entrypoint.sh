#!/bin/sh
rustfs &

# Attendre que le serveur soit prêt
echo "Attente du démarrage de rustfs..."
until mc alias set local http://127.0.0.1:9000 $RUSTFS_ACCESS_KEY $RUSTFS_SECRET_KEY > /dev/null 2>&1; do
  sleep 1
done
echo "rustfs est prêt."

# Traiter la liste des buckets
if [ -n "$DEFAULT_BUCKETS" ]; then
  echo "Création des buckets par défaut spécifiés..."

  # Utiliser IFS pour diviser la chaîne par des virgules
  OLDIFS=$IFS
  IFS=', '
  for bucket_config in $DEFAULT_BUCKETS; do
    # Extraire le nom du bucket et la politique
    bucket_name=$(echo $bucket_config | cut -d ':' -f 1)
    policy=$(echo $bucket_config | cut -d ':' -f 2)

    echo "Création du bucket: $bucket_name"
    mc mb --ignore-existing local/$bucket_name

    # Appliquer la politique directement
    echo "Configuration des permissions pour $bucket_name: $policy"
    mc anonymous set $policy local/$bucket_name
  done
  IFS=$OLDIFS

  echo "Tous les buckets par défaut ont été créés et configurés."
else
  echo "Aucun bucket par défaut spécifié dans DEFAULT_BUCKETS."
fi

tail -f /dev/null
