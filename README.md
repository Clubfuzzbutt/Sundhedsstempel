# Økonomisk sundhedsstempel – Prototype

Dette er en prototype til et økonomisk sundhedsstempel udviklet som diplomprojekt i samarbejde med Move On Career.
Prototypen henter offentligt tilgængelige regnskabsdata fra Erhvervsstyrelsens API, beregner nøgletal (soliditetsgrad, overskudsgrad og likviditetsgrad) og viser et farvekodet stempel (grøn/gul/rød).

##  Online demo

Prototypen er hostet på InfinityFree og kan afprøves her:

**https://sundhedsstempel.infinityfree.me/index.html**

*Bemærk: InfinityFree er en gratis hostingplatform og kan være ustabil. Hvis linket ikke virker, anbefales lokal installation (se nedenfor).*

---

## Lokal installation (XAMPP)

### Krav
- [XAMPP](https://www.apachefriends.org/) (Apache + PHP)
- En browser (Chrome, Edge eller Firefox)

### Installation

1. **Download og installer XAMPP**
   - Gå til [apachefriends.org](https://www.apachefriends.org/)
   - Download XAMPP til dit styresystem (Windows, Mac eller Linux)
   - Installer XAMPP (standardindstillinger er fine)

2. **Hent prototypen**
   - Download ZIP-filen med prototypen
   - Udpak ZIP-filen

3. **Placer filerne**
   - Kopiér den udpakkede mappe til XAMPP's `htdocs`-mappe. F.eks.:
     - **Windows:** `C:\xampp\htdocs\`

4. **Start XAMPP**
   - Åbn XAMPP Control Panel
   - Klik på **"Start"** ved siden af **Apache**

5. **Åbn prototypen – vælg én af disse to måder:**

   **Mulighed A:**
   -Hvis filen er placeret korrent, kan den åbnes via link: http://localhost/sundhedsstempel/Frontend/html/demo.html

   **Mulighed B (direkte i mappen – virker også):**
   - Find filen i mappen: sundhedsstempel -> Frontend -> html -> demo.html
   - Dobbeltklik på demo.html – den åbner i din browser

6. **Afprøv prototypen**
- Indtast et CVR-nummer (f.eks. 10117224 for Maxi Zoo)
   - Klik på "Søg"
   - Stemplet og nøgletallene vises

## Fejlfinding

Problem: "Fejl ved forbindelse"
Løsning: Tjek at XAMPP Apache kører. Åbn http://localhost i browseren – hvis du ser XAMPP's velkomstside, virker serveren.

Problem: "Ingen data fundet"
Løsning: CVR-nummeret findes ikke, eller virksomheden har ikke indberettet regnskab i XBRL-format. Prøv et andet CVR-nummer.

Problem: Apache starter ikke
Løsning: Port nr som apache kører på er optaget. Luk programmet som optager porten, eller gå til XAMPP panelet =>Config => httpd-ssl.conf
skift Listen 443 til anden port, f.eks 4433.
 