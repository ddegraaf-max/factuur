#!/usr/bin/env sh
# Zet het beeldmerk en het logo van elk merk als bestand in Stripe, en geeft de
# bestand-id's terug. Die id's gaan daarna in config/brand.php, zodat elke
# Checkout-sessie het beeldmerk van zijn eigen merk meekrijgt.
#
# De bestanden staan al in de container; er hoeft niets te worden overgezet.
# Een upload is idempotent gemaakt door eerst te kijken of er al een bestand met
# dezelfde naam is; anders krijg je bij elke deploy een nieuwe kopie.

set -u
S="$STRIPE_SECRET"

zoek_of_upload() {
  pad="$1"; doel="$2"
  naam=$(basename "$pad")
  [ -f "$pad" ] || { echo "MIST"; return; }

  bestaand=$(curl -s -u "$S:" "https://api.stripe.com/v1/files?purpose=$doel&limit=100" \
    | tr '{' '\n' | grep "\"filename\": \"$naam\"" | grep -m1 -o 'file_[A-Za-z0-9]*' | head -1)
  if [ -n "$bestaand" ]; then echo "$bestaand"; return; fi

  curl -s -u "$S:" https://files.stripe.com/v1/files -F "purpose=$doel" -F "file=@$pad" \
    | tr ',' '\n' | grep -m1 '"id": "file_' | sed 's/.*"id": "//;s/"//'
}

echo "== EasyInvoice"
echo "   icon: $(zoek_of_upload /app/public/images/easyinvoice-icon-512.png business_icon)"
echo "   logo: $(zoek_of_upload /app/public/images/easyinvoice-logo-horizontal-red.png business_logo)"

echo "== EasyBookkeeper"
echo "   icon: $(zoek_of_upload /app/public/brand/easybookkeeper/eb-icon-512.png business_icon)"

echo "== Lopra"
echo "   icon: $(zoek_of_upload /app/public/brand/lopra/lopra-icon-512.png business_icon)"
echo "   logo: $(zoek_of_upload /app/public/brand/lopra/lopra-logo-800.png business_logo)"

echo KLAAR
