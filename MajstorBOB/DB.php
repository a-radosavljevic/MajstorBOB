<?php

include_once 'Zanat.php';
include_once 'Lokacija.php';
include_once 'Ocenjuje.php';
include_once 'Majstor.php';
include_once 'Oglas.php';
include_once 'Musterija.php';
include_once 'Komentar.php';
include_once 'dogadjaj.php';
require_once __DIR__ . "/vendor/autoload.php";
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class DB {

    public function vratiKompletnoMajstor($email) {
        $client = new MongoDB\Client;
        $majstoricoll = (new MongoDB\Client)->mongo_baza->majstor;
        $majstoricoll = (new MongoDB\Client)->mongo_baza->majstor;
        $r = $majstoricoll->findOne(['majstor_Email' => $email]);
        $Majstor = new Majstor($r["majstor_Email"], $r["password"], $r["ime"], $r["prezime"], $r["kontakt"], $r["kvalifikacije"], $r["adresa"], $r["vreme_radni_dan"], $r["vreme_subota"], $r["vreme_nedelja"], $r["izlazak_na_teren"], $r["majstor_hint_pitanje"], $r["majstor_hint_odgovor"], $r["dostupnost_za_rad"], $r["online"]);

        $zanati = $this->vratiZanateMajstora($Majstor->majstor_Email);
        $Majstor->dodajSveZanate($zanati);
        $lokacije = $this->vratiGradoveMajstora($Majstor->majstor_Email);

        $Majstor->dodajSveLokacije($lokacije);
        $Majstor->ocena = $this->prosecnaOcena($Majstor->majstor_Email);

        $komentari = $this->vratiKomentare($email);
        $Majstor->komentari = $komentari;
        return $Majstor;
    }

    public function vratiNajboljegOcenjenogMajstora() {
        $ocenjujecoll = (new MongoDB\Client)->mongo_baza->ocenjuje;
        $rr = $ocenjujecoll->aggregate([
            [
                '$group' =>
                [
                    '_id' => '$majstor_Email0',
                    'musterijaEmail' => ['$first' => '$musterija_Email1'],
                    'prosek' => ['$avg' => ['$toInt' => '$ocena']]
                ]
            ]
            ,
            ['$sort' =>
                ['prosek' => -1]]
        ]);
        $array = iterator_to_array($rr);
        $r = $array[0];
        $ocena = new Ocenjuje($r["_id"], $r["musterijaEmail"], round($r["prosek"], 2, PHP_ROUND_HALF_UP));
        return $ocena;
    }

    public function vratiGradoveMajstora($email) {
        $radinacoll = (new MongoDB\Client)->mongo_baza->radi_na;
        $query = array('majstor_Email0' => $email);
        $res = $radinacoll->find($query);
        $niz = array();
        foreach ($res as $r) {
            $niz[] = new Lokacija($r["mesto1"]);
        }
        return $niz;
    }

    public function vratiPrijavljeneOglase() {
        $oglasicoll = (new MongoDB\Client)->mongo_baza->prijava_nepozeljnih__oglasa;
        $res = $oglasicoll->find();
        $oglasi = array();
        foreach ($res as $r) {
            $oglasi[] = new PrijavljeniOglasi($r["majstor_Email0"], $r["vrsta_posla_zanata3"]);
        }
        return $oglasi;
    }

    public function vratiPrijavljeneProfile() {
        $profilicoll = (new MongoDB\Client)->mongo_baza->prijava_nezeljenih_profila;
        $res = $profilicoll->find();
        $profili = array();
        foreach ($res as $r) {
            $profili[] = new PrijavljeniProfili($r["prijavljeni_profil"], $r["majstor_Email0"]);
        }
        return $profili;
    }

    public function vratiGradove() {
        $lokacijacoll = (new MongoDB\Client)->mongo_baza->lokacija;

        $options = ['sort' => ['mesto' => 1]];
        $res = $lokacijacoll->find([], $options);
        $l = array();
        foreach ($res as $r) {

            $l[] = new Lokacija($r["mesto"]);
        }
        return $l;
    }

    public function vratiZanateMajstora($email) {
        //echo"<br>";
        $zanaticoll = (new MongoDB\Client)->mongo_baza->poseduje_zanate;
        $query = array('majstor_Email0' => $email);
        $res = $zanaticoll->find($query);
        $niz = array();
        foreach ($res as $r) {

            $niz[] = new Zanat($r['tip1']);
        }
        //echo"<br>";
        //print_r($niz);
        return $niz;
    }

    public function vratiZanate() {
        $zanatcoll = (new MongoDB\Client)->mongo_baza->zanat;

        $options = ['sort' => ['tip' => 1]];
        $res = $zanatcoll->find([], $options);
        $z = array();
        foreach ($res as $r) {

            $z[] = new Zanat($r["tip"]);
        }
        //print_r($z);
        return $z;
    }

    public static function vratiMajstora($email) {
        $client = new MongoDB\Client;
        $majstoricoll = (new MongoDB\Client)->mongo_baza->majstor;
        $majstoricoll = (new MongoDB\Client)->mongo_baza->majstor;
        $r = $majstoricoll->findOne(['majstor_Email' => $email]);
        $Majstor = new Majstor($r["majstor_Email"], $r["password"], $r["ime"], $r["prezime"], $r["kontakt"], $r["kvalifikacije"], $r["adresa"], $r["vreme_radni_dan"], $r["vreme_subota"], $r["vreme_nedelja"], $r["izlazak_na_teren"], $r["majstor_hint_pitanje"], $r["majstor_hint_odgovor"], $r["dostupnost_za_rad"], $r["online"]);

        $zanati = $this->vratiZanateMajstora($Majstor->majstor_Email);
        $Majstor->dodajSveZanate($zanati);
        $lokacije = $this->vratiGradoveMajstora($Majstor->majstor_Email);

        $Majstor->dodajSveLokacije($lokacije);
        $Majstor->ocena = $this->prosecnaOcena($Majstor->majstor_Email);


        return $Majstor;
    }

    public function onlineMajstori() {//majstori dostupni za rad
        $client = new MongoDB\Client;
        $majstoricoll = (new MongoDB\Client)->mongo_baza->majstor;
        $query = array('online' => 'DA');
        $rr = $majstoricoll->find($query);
        $majstori = array();

        foreach ($rr as $r) {
            $Majstor = new Majstor($r["majstor_Email"], $r["password"], $r["ime"], $r["prezime"], $r["kontakt"], $r["kvalifikacije"], $r["adresa"], $r["vreme_radni_dan"], $r["vreme_subota"], $r["vreme_nedelja"], $r["izlazak_na_teren"], $r["majstor_hint_pitanje"], $r["majstor_hint_odgovor"], $r["dostupnost_za_rad"], $r["online"]);

            $majstori[] = $Majstor;
        }


        return $majstori;
    }

    public static function vratiSveMajstore() { //vraca sve majstore koji postoje u bazi
        $majstorcoll = (new MongoDB\Client)->mongo_baza->majstor;
        $res = $majstorcoll->find();
        $majstori = array();
        foreach ($res as $r) {
            $majstor = new Majstor($r["majstor_Email"], $r["password"], $r["ime"], $r["prezime"], $r["kontakt"], $r["kvalifikacije"], $r["adresa"], $r["vreme_radni_dan"], $r["vreme_subota"], $r["vreme_nedelja"], $r["izlazak_na_teren"], $r["majstor_hint_pitanje"], $r["majstor_hint_odgovor"], $r["dostupnost_za_rad"], $r["online"]);

            $majstori[] = $majstor;
        }
        return $majstori;
    }

    public function vratiSveMajstorePoLokaciji($lok) { //vraca sve majstore koji postoje u bazi
        $majstorcoll = (new MongoDB\Client)->mongo_baza->radi_na;
        $query = array("mesto1" => $lok);
        $res = $majstorcoll->find($query);
        $majstori = array();
        foreach ($res as $r) {
            $majstor = $this->vratiKompletnoMajstor($r["majstor_Email0"]);


            $majstori[] = $majstor;
        }
        return $majstori;
    }

    public static function vratiSveMajstorePoZanatu($zanat) {
        $majstorcoll = (new MongoDB\Client)->mongo_baza->poseduje_zanate;
        $query = array("tip1" => $zanat);
        $res = $majstorcoll->find($query);
        $majstori = array();
        foreach ($res as $r) {
            $majstor = $this->vratiKompletnoMajstor($r["majstor_Email0"]);


            $majstori[] = $majstor;
        }
        return $majstori;
    }

    public function prosecnaOcena($email) {

        $ocenjujecoll = (new MongoDB\Client)->mongo_baza->ocenjuje;
        $rr = $ocenjujecoll->aggregate([
            ['$match' => ['majstor_Email0' => $email]],
            [
                '$group' =>
                [
                    '_id' => '$majstor_Email0',
                    'musterijaEmail' => ['$first' => '$musterija_Email1'],
                    'prosek' => ['$avg' => ['$toInt' => '$ocena']]
                ]
        ]]);
        $array = iterator_to_array($rr);
        $r = current($array);
        $ocena = round($r["prosek"], 2, PHP_ROUND_HALF_UP);

        return $ocena;
    }

    public function dajOcenu($emailMajstor, $emailMusterija, $ocena) {

        $ocenacoll = (new MongoDB\Client)->mongo_baza->ocenjuje;
        $query = array('majstor_Email0' => $emailMajstor, 'musterija_Email1' => $emailMusterija, 'ocena' => $ocena);
        $ocenacoll->insertOne($query);
    }

    public function vratiMusteriju($email) {

        $musterijacoll = (new MongoDB\Client)->mongo_baza->musterija;
        $query = array('musterija_Email' => $email);
        $r = $musterijacoll->findOne($query);

        $musterija = new Musterija($r["musterija_Email"], $r["password"], $r["ime"], $r["prezime"], $r["kontakt"], $r["lokacija"], $r["musterija_hint_pitanje"], $r["musterija_hint_odgovor"]);
        return $musterija;
    }

    public static function majstorRegistrovanje(Majstor $majstor) { //pod uslovom da smo podatke o majstoru pokupili iz forme
        $majstorcoll = (new MongoDB\Client)->mongo_baza->majstor;
        $query = array('majstor_Email' => $majstor->majstor_Email, 'password' => $majstor->password, 'ime' => $majstor->ime, 'prezime' => $majstor->prezime, 'kontakt' => $majstor->kontakt, 'kvalifikacije' => $majstor->kvalifikacije,
            'vreme_radni_dan' => $majstor->vreme_radni_dan, 'vreme_subota' => $majstor->vreme_subota, 'vreme_nedelja' => $majstor->vreme_nedelja, 'adresa' => $majstor->adresa, 'majstor_hint_pitanje' => $majstor->majstor_hint_pitanje,
            'majstor_hint_odgovor' => $majstor->majstor_hint_odgovor, 'izlazak_na_teren' => $majstor->izlazak_na_teren, 'dostupnost_za_rad' => $majstor->dostupnost_za_rad, 'online' => $majstor->online);
        $majstorcoll->insertOne($query);
    }

    public function musterijaRegistrovanje(Musterija $musterija) {
        $musterijacoll = (new MongoDB\Client)->mongo_baza->musterija;
        $query = array('musterija_Email' => $musterija->musterija_Email, 'password' => $musterija->password, 'ime' => $musterija->ime, 'prezime' => $musterija->prezime, 'kontakt' => $musterija->kontakt, 'lokacija' => $musterija->lokacija,
            'musterija_hint_pitanje' => $musterija->musterija_hint_pitanje,
            'musterija_hint_odgovor' => $musterija->musterija_hint_odgovor);
        $musterijacoll->insertOne($query);
    }

    public function vratiPorukeMajstor($majstor_email) { //sve poruke za jednog majstora
        $razmenacoll = (new MongoDB\Client)->mongo_baza->razmena_poruka;
        $query = array('majstor_Email0' => $majstor_email);
        $res = $razmenacoll->find($query);
        $poruke = array();
        foreach ($res as $r) {
            $poruke[] = new Poruka($r["majstor_Email0"], $r["musterija_Email1"], $r["tekst_poruke"], $r["datum_slanja_poruke"], $r["posiljaoc"]);
        }
        return $poruke;
    }

    public function vratiPorukeMusterija($musterija_email) {
        $razmenacoll = (new MongoDB\Client)->mongo_baza->razmena_poruka;
        $query = array('musterija_Email1' => $musterija_email);
        $res = $razmenacoll->find($query);
        $poruke = array();
        foreach ($res as $r) {
            $poruke[] = new Poruka($r["majstor_Email0"], $r["musterija_Email1"], $r["tekst_poruke"], $r["datum_slanja_poruke"], $r["posiljaoc"]);
        }
        return $poruke;
    }

    public function vratiKonverzaciju($majstor, $musterija) {
        $razmenacoll = (new MongoDB\Client)->mongo_baza->razmena_poruka;
        $query = array('majstor_Email0' => $majstor, 'musterija_Email1' => $musterija);
        $options = array("sort" => array("datum_slanja_poruke" => 1));
        $res = $razmenacoll->find($query, $options);
        $poruke = array();
        foreach ($res as $r) {
            $poruke[] = new Poruka($r["majstor_Email0"], $r["musterija_Email1"], $r["tekst_poruke"], $r["datum_slanja_poruke"], $r["posiljaoc"]);
        }
        return $poruke;
    }

    public function vratiRazgovoreZaMusteriju($email) {
        $razmenacoll = (new MongoDB\Client)->mongo_baza->razmena_poruka;
        $res = $razmenacoll->aggregate([['$match' => ['musterija_Email1' => $email]], ['$group' => ['_id' => '$majstor_Email0', 'datum' => ['$max' => '$datum_slanja_poruke']]], ['$sort' =>
                ['datum' => -1]]
        ]);
        $poruke = array();
        foreach ($res as $r) {

            $poruke[] = new Poruka($r["_id"], $email, "", "", "");
        }
        //var_dump($poruke);
        return $poruke;
    }

    //$conn=new MongoDB\Client("mongodb://localhost:27017"); //$conn->close(); zastarelo?
    public function updateMusterija($musterija) {
        $musterije = (new MongoDB\Client)->mongo_baza->musterija;  // gde se tacno nalazi???????????????????????
        if (!$musterije)
            print("Connection error");
        $updateResult = $musterije->updateOne(
                ['musterija_Email' => $musterija->musterija_Email], ['$set' => ['password' => $musterija->password,
                'ime' => $musterija->ime,
                'prezime' => $musterija->prezime,
                'kontakt' => $musterija->kontakt,
                'lokacija' => $musterija->lokacija,
                'musterija_hint_pitanje' => $musterija->musterija_hint_pitanje,
                'musterija_hint_odgovor' => $musterija->musterija_hint_odgovor]]
        );
        if ($updateResult) {
            
        } else {
            print("Query failed");
        }
    }

    public function updateKompletnoMajstor(Majstor $majstor) {
        $majstori = (new MongoDB\Client)->mongo_baza->majstor;  // gde se tacno nalazi???????????????????????
        if (!$majstori)
            print("Connection error");

        $updateResult = $majstori->updateOne(
                ['majstor_Email' => $majstor->majstor_Email], ['$set' => ['password' => $majstor->password,
                'ime' => $majstor->ime,
                'prezime' => $majstor->prezime,
                'kontakt' => $majstor->kontakt,
                'adresa' => $majstor->adresa,
                'majstor_hint_pitanje' => $majstor->majstor_hint_pitanje,
                'majstor_hint_odgovor' => $majstor->majstor_hint_odgovor,
                'kvalifikacije' => $majstor->kvalifikacije,
                'vreme_radni_dan' => $majstor->vreme_radni_dan,
                'vreme_subota' => $majstor->vreme_subota,
                'vreme_nedelja' => $majstor->vreme_nedelja,
                'dostupnost_za_rad' => $majstor->dostupnost_za_rad,
                'izlazak_na_teren' => $majstor->izlazak_na_teren,
                'online' => $majstor->online]]
        );

        if ($updateResult) {
            $this->obrisiZanateMajstora($majstor->majstor_Email);
            foreach ($majstor->zanati as $zanat) {
                $this->upisiZanateMajstora($majstor->majstor_Email, $zanat->tip);
            }
            $this->obrisiGradoveMajstora($majstor->majstor_Email);
            foreach ($majstor->lokacije as $l) {
                $this->upisiGradoveZaMajstora($majstor->majstor_Email, $l->mesto);
            }
        } else {
            print("Query failed");
        }
    }

    public function obrisiKomentareMajstora($email) {
        $komentari = (new MongoDB\Client)->mongo_baza->komentari;  // gde se tacno nalazi???????????????????????
        if (!$komentari) {
            print("Connection error");
        } else {
            $deleteResult = $komentari->deleteMany(['majstor_Email1' => '$email']);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiOceneMajstora($email) {
        $ocene = (new MongoDB\Client)->mongo_baza->ocenjuje;  // gde se tacno nalazi???????????????????????
        if (!$ocene) {
            print("Connection error");
        } else {
            $deleteResult = $ocene->deleteMany(['majstor_Email0' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiSliku($email) {
        $fajlovi_u_bazi = (new MongoDB\Client)->mongo_baza->fajl_u_bazi;  // gde se tacno nalazi???????????????????????
        if (!$fajlovi_u_bazi) {
            print("Connection error");
        } else {
            $deleteResult = $fajlovi_u_bazi->deleteOne(['email_korisnika' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiPrijaveZaPosao($email) {
        $prijave_za_posao = (new MongoDB\Client)->mongo_baza->prijava_za_posao;  // gde se tacno nalazi???????????????????????
        if (!$prijave_za_posao) {
            print("Connection error");
        } else {
            $deleteResult = $prijave_za_posao->deleteMany(['majstor_Email3' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiPorukeMajstor($email) {

        $poruke = (new MongoDB\Client)->mongo_baza->razmena_poruka;  // gde se tacno nalazi???????????????????????
        if (!$poruke) {
            print("Connection error");
        } else {
            $deleteResult = $poruke->deleteMany(['majstor_Email0' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiMajstora($email) {
        $majstori = (new MongoDB\Client)->mongo_baza->majstor;  // gde se tacno nalazi???????????????????????
        if (!$majstori) {
            print("Connection error");
        } else {
            $deleteResult = $majstori->deleteOne(['majstor_Email' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiMusteriju($email) {
        $musterije = (new MongoDB\Client)->mongo_baza->musterija;  // gde se tacno nalazi???????????????????????
        if (!$musterije) {
            print("Connection error");
        } else {
            $deleteResult = $musterije->deleteOne(['musterija_Email' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiPorukeMusterije($email) {
        $poruke = (new MongoDB\Client)->mongo_baza->razmena_poruka;  // gde se tacno nalazi???????????????????????
        if (!$poruke) {
            print("Connection error");
        } else {
            $deleteResult = $poruke->deleteMany(['musterija_Email1' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiKomentareMusterija($email) {
        $komentari = (new MongoDB\Client)->mongo_baza->ostavljanje_komentara;  // gde se tacno nalazi???????????????????????
        if (!$komentari) {
            print("Connection error");
        } else {
            $deleteResult = $komentari->deleteMany(['musterija_Email0' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiOceneMusterija($email) {
        $ocene = (new MongoDB\Client)->mongo_baza->ocenjuje;  // gde se tacno nalazi???????????????????????
        if (!$ocene) {
            print("Connection error");
        } else {
            $deleteResult = $ocene->deleteMany(['musterija_Email1' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiOglaseMusterija($email) {
        $oglasi = (new MongoDB\Client)->mongo_baza->oglas;  // gde se tacno nalazi???????????????????????
        if (!$oglasi) {
            print("Connection error");
        } else {
            $deleteResult = $oglasi->deleteMany(['musterija_Email5' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiPrijaveZaPosaoMusterija($email) {
        $prijave = (new MongoDB\Client)->mongo_baza->prijava_za_posao;  // gde se tacno nalazi???????????????????????
        if (!$prijave) {
            print("Connection error");
        } else {
            $deleteResult = $prijave->deleteMany(['musterija_Email5' => $email]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function vratiOglasPoId($id) {
        //var_dump($id);
        $oglasi = (new MongoDB\Client)->mongo_baza->oglas;  // gde se tacno nalazi???????????????????????
        if (!$oglasi) {
            print("Connection error");
        } else {
            $doc = $oglasi->findOne(['id' => $id]);
            $ogl = null;
            //var_dump($doc);
            if ($doc) {
                $ogl = new Oglas($doc['tekst_oglasa'], $doc['datum'], $doc['adresa'], $doc['vrsta_posla_zanata'], $doc['musterija_Email5'], $doc['mesto6']);
                
                if($doc['majstor_koji_radi_email'] != 0)
                    $ogl->dodajMajstora($doc['majstor_koji_radi_email']);

                return $ogl;
            } else {
                print("Query failed");
            }
        }
    }

    public function novaPrijavaOglasa($id, $razlog) {
        $prijave = (new MongoDB\Client)->mongo_baza->prijava_nepozeljnih__oglasa;  // gde se tacno nalazi???????????????????????
        if (!$prijave) {
            print("Connection error");
        } else {
            $insertOneResult = $collection->insertOne([
                'majstor_Email0' => $id,
                'vrsta_posla_zanata3' => $razlog
            ]);

            if ($insertOneResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function novaPrijavaProfila($id, $razlog) {
        $prijave = (new MongoDB\Client)->mongo_baza->prijava_nezeljenih_profila;  // gde se tacno nalazi???????????????????????
        if (!$prijave) {
            print("Connection error");
        } else {
            $insertOneResult = $collection->insertOne([
                'majstor_Email0' => $razlog,
                'prijavljeni_profil' => $id
            ]);

            if ($insertOneResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function vratiSveOglase() {
        $oglasi = (new MongoDB\Client)->mongo_baza->oglas;  
        if (!$oglasi) {
            print("Connection error");
        } else {
            $cursor = $oglasi->find(
                    [], [
                'sort' => ['datum' => -1], 
                    ]
            );
            $niz = array();
            if ($cursor) {
                foreach ($cursor as $doc) {
                    $niz[$doc['id']] = new Oglas($doc['tekst_oglasa'], $doc['datum'], $doc['adresa'], $doc['vrsta_posla_zanata'], $doc['musterija_Email5'], $doc['mesto6']);
                }
                return $niz;
            } else {
                print("Query failed");
            }
        }
    }

    public function vratiOglasePoMestu($mesto) {
        $oglasi = (new MongoDB\Client)->mongo_baza->oglas;  // gde se tacno nalazi???????????????????????
        if (!$oglasi) {
            print("Connection error");
        } else {
            $cursor = $oglasi->find(['mesto6' => $mesto]);
            $niz = array();
            if ($cursor) {
                foreach ($cursor as $doc) {
                    $niz[$doc['id']] = new Oglas($doc['tekst_oglasa'], $doc['datum'], $doc['adresa'], $doc['vrsta_posla_zanata'], $doc['musterija_Email5'], $doc['mesto6']);
                }
                return $niz;
            } else {
                print("Query failed");
            }
        }
    }

    public function vratiOglasePoKategoriji($kat) {
        $oglasi = (new MongoDB\Client)->mongo_baza->oglas;  // gde se tacno nalazi???????????????????????
        if (!$oglasi) {
            print("Connection error");
        } else {
            $cursor = $oglasi->find(['vrsta_posla_zanata' => $kat]);
            $niz = array();
            if ($cursor) {
                foreach ($cursor as $doc) {
                    $niz[$doc['id']] = new Oglas($doc['tekst_oglasa'], $doc['datum'], $doc['adresa'], $doc['vrsta_posla_zanata'], $doc['musterija_Email5'], $doc['mesto6']);
                }
                return $niz;
            } else {
                print("Query failed");
            }
        }
    }

    public function vratiOglasePoKategorijiIMestu($mesto, $kat) {
        $oglasi = (new MongoDB\Client)->mongo_baza->oglas;  // gde se tacno nalazi???????????????????????
        if (!$oglasi) {
            print("Connection error");
        } else {
            $cursor = $oglasi->find(['mesto6' => $mesto, 'vrsta_posla_zanata' => $kat]);
            $niz = array();
            if ($cursor) {
                foreach ($cursor as $doc) {
                    $niz[$doc['id']] = new Oglas($doc['tekst_oglasa'], $doc['datum'], $doc['adresa'], $doc['vrsta_posla_zanata'], $doc['musterija_Email5'], $doc['mesto6']);
                }
                return $niz;
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiOglas($id) {
        $oglasi = (new MongoDB\Client)->mongo_baza->oglas;  // gde se tacno nalazi???????????????????????
        if (!$oglasi) {
            print("Connection error");
        } else {
            $deleteResult = $oglasi->deleteOne(['id' => $id]);

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiNezeljeniOglas($id) {
        $oglasi = (new MongoDB\Client)->mongo_baza->prijava_nepozeljnih__oglasa;  // gde se tacno nalazi???????????????????????
        if (!$oglasi) {
            print("Connection error");
        } else {
            $deleteResult = $oglasi->deleteOne(['majstor_Email0' => $id]); // treba 1 da se brise??

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function obrisiNezeljeniProfil($id) {
        $prijave = (new MongoDB\Client)->mongo_baza->prijava_nezeljenih_profila;  // gde se tacno nalazi???????????????????????
        if (!$prijave) {
            print("Connection error");
        } else {
            $deleteResult = $prijave->deleteOne(['prijavljeni_profil' => $id]); // treba 1 da se brise??

            if ($deleteResult) {
                
            } else {
                print("Query failed");
            }
        }
    }

    public function vratiOglaseMusterije($email) {
        $oglasi = (new MongoDB\Client)->mongo_baza->oglas;  // gde se tacno nalazi???????????????????????
        if (!$oglasi) {
            print("Connection error");
        } else {
            $cursor = $oglasi->find(['musterija_Email5' => $email]);
            $niz = array();
            if ($cursor) {
                foreach ($cursor as $doc) {
                    $niz[$doc['id']] = new Oglas($doc['tekst_oglasa'], $doc['datum'], $doc['adresa'], $doc['vrsta_posla_zanata'], $doc['musterija_Email5'], $doc['mesto6']);
                }
                return $niz;
            } else {
                print("Query failed");
            }
        }
    }

    public function oglasi() {
        $oglasi = (new MongoDB\Client)->mongo_baza->oglas;  // gde se tacno nalazi???????????????????????
        if (!$oglasi) {
            print("Connection error");
        } else {
            $cursor = $oglasi->find([]); // OBAVEZNO POGLEDAJ!!!!!!
            /* $res=$con->query("SELECT * FROM oglas WHERE datum>(CURDATE()-INTERVAL 5 DAY)"); */
            $niz = array();
            if ($cursor) {
                foreach ($cursor as $doc) {
                    $niz[$doc['id']] = new Oglas($doc['tekst_oglasa'], $doc['datum'], $doc['adresa'], $doc['vrsta_posla_zanata'], $doc['musterija_Email5'], $doc['mesto6']);
                }
                return $niz;
            } else {
                print("Query failed");
            }
        }
    }

    public function majstor($email) {
        $majstori = (new MongoDB\Client)->mongo_baza->majstor;  // gde se tacno nalazi???????????????????????
        if (!$majstori) {
            print("Connection error");
        } else {
            $doc = $majstori->findOne(['majstor_Email' => $email]);
            //var_dump($doc);
            $Majstor = NULL;
            if ($doc) {
                $Majstor = new Majstor($doc['majstor_Email'], $doc['password'], $doc['ime'], $doc['prezime'], $doc['kontakt'], $doc['kvalifikacije'], $doc['adresa'], $doc['vreme_radni_dan'], $doc['vreme_subota'], $doc['vreme_nedelja'], $doc['adresa'], $doc['majstor_hint_pitanje'], $doc['majstor_hint_odgovor'], $doc['izlazak_na_teren'], $doc['dostupnost_za_rad'], $doc['online']);
                //var_dump($Majstor);
                return $Majstor;
            } else {
                print("Query failed");
            }
        }
    }

    public function vratiPrijave($idOglas) {
        $collection = (new MongoDB\Client)->mongo_baza->prijava_za_posao;
        $cursor = $collection->find(['idOglasa' => $idOglas]);
        $prijave = array();
        $email = "";
        foreach ($cursor as $document) {
            $email = $document['majstor_Email3'];
            $prijave[] = $email;
        }
        return $prijave;
    }

    public function offlineMajstor($email) {
        $collection = (new MongoDB\Client)->mongo_baza->majstor;
        $updateResult = $collection->updateOne(
                ['majstor_Email' => $email], ['$set' => ['online' => 'NE']]
        );
    }

    public function onlajnMajstor($email) {
        $collection = (new MongoDB\Client)->mongo_baza->majstor;
        $updateResult = $collection->updateOne(
                ['majstor_Email' => $email], ['$set' => ['online' => 'DA']]
        );
    }

    public function vratiDogadjaje($datum, $email) {//potencijalni problem datum,    potencijalni problem ID,treba prilikom inserta obezbediti ovo
        $collection = (new MongoDB\Client)->mongo_baza->dogadjaj;
        $cursor = $collection->find(['datum' => $datum, 'emailKorisnika' => $email]);
        $niz = array();

        foreach ($cursor as $document) {

            $niz[$document['id']] = new dogadjaj($document["datum"], $document["emailKorisnika"], $document["vreme"], $document["nazivDogadjaja"]);
        }

        return $niz;
    }

    public function vratiDogadjajeEmail($email) {//potencijalni problem datum,   potencijalni problem ID treba prilikom inserta obezbediti ovo

        $collection = (new MongoDB\Client)->mongo_baza->dogadjaj;
        $cursor = $collection->find(['emailKorisnika' => $email]);
        $niz = array();

        foreach ($cursor as $document) {
            if ($document["datum"] >= date('Y-m-d H:i:s', strtotime('-1 day')))
                $niz[$document['id']] = new dogadjaj($document["datum"], $document["emailKorisnika"], $document["vreme"], $document["nazivDogadjaja"]);
        }//svi dogadjaji za zadati email pa onda proverimo datum
        //$niz_Za_Vreme_minus_jedan=array();

        return $niz;
    }

    public function vratiDatumeEmail($email) {
        //SELECT DISTINCT datum FROM dogadjaj 
        //WHERE emailKorisnika='$email' AND datum>=NOW()-1 ORDER BY datum, vreme
        $collection = (new MongoDB\Client)->mongo_baza->dogadjaj;
        /* $cursor = $collection->aggregate([
          ['$match' => ['emailKorisnika'=>$email]],
          ['$group' => ['_id'=>'$emailKorisnika','datum'=>['$first' => '$datum']]],
          ['$sort' => ['datum' => -1,'vreme'=>-1]],
          ['$limit' => 5],
          ]); */
        $cursor = $collection->find(['emailKorisnika' => $email]);

        $niz = array();
        foreach ($cursor as $state) {
            //if($state['datum']>=date('Y-m-d H:i:s', strtotime('-1 day')))
            if (!in_array($state['datum'], $niz))
                $niz[] = $state['datum'];
        }
        //var_dump($niz);
        return $niz;
    }

    public function dodajDogadjaj(Dogadjaj $d) {
        $collection = (new MongoDB\Client)->mongo_baza->dogadjaj;


        $cursor = $collection->find(
                [], [
            'limit' => 1,
            'sort' => ['id' => -1],
                ]
        );

        foreach ($cursor as $state) {
            $a = $state['id'];
        }
        //var_dump($a);
        $a = $a + 1;
        $insertOneResult = $collection->insertOne(['id' => $a, 'datum' => $d->datum, 'emailKorisnika' => $d->emailKorisnika, 'vreme' => $d->vreme,
            'nazivDogadjaja' => $d->opisDogadjaja]);
    }

    public function obrisiDogadjaj($id) {
        $collection = (new MongoDB\Client)->mongo_baza->dogadjaj;
        $deleteResult = $collection->deleteMany(['id' => intval($id)]);
        //var_dump($deleteResult);
    }

    public function izmeniDogadjaj($id, Dogadjaj $d) {
        $collection = (new MongoDB\Client)->mongo_baza->dogadjaj;
        $updateResult = $collection->replaceOne(
                ['id' => $id], ['datum' => $d->datum, 'emailKorisnika' => $d->emailKorisnika, 'vreme' => $d->vreme, 'nazivDogadjaja' => $d->opisDogadjaja]
        );
    }

    public function obrisiDogadjajeMajstora($email) {
        $collection = (new MongoDB\Client)->mongo_baza->dogadjaj;
        $deleteResult = $collection->deleteMany(['emailKorisnika' => $email]);
    }

    public function vratiMajstorePoZanatuILokaciji($z, $lokacija) {
        $collection = (new MongoDB\Client)->mongo_baza->radi_na;
        $cursor1 = $collection->find(['tip1' => $z]);

        $collection = (new MongoDB\Client)->mongo_baza->poseduje_zanate;
        $cursor2 = $collection->find(['mesto1' => $lokacija]);
        $emailMajstora_zanati_lokacije = array();

        foreach ($cursor1 as $document1) {

            foreach ($cursor2 as $document2) {

                if ($document1['majstor_Email0'] == $document2['majstor_Email0'])
                    $emailMajstora_zanati_lokacije[] = $document1['majstor_Email0']; //naso sam majstore koji rade na lokaciji i imaju te zanate
            }
        }

        $collection = (new MongoDB\Client)->mongo_baza->majstor;

        $majstor = null;
        $majstori = array();

        foreach ($emailMajstora_zanati_lokacije as $em) {
            $row = $collection->findOne(['majstor_Email' => $em]);

            $majstor = new Majstor($row["majstor_Email"], $row["password"], $row["ime"], $row["prezime"], $row["kontakt"], $row["kvalifikacije"], $row["adresa"], $row["vreme_radni_dan"], $row["vreme_subota"], $row["vreme_nedelja"], $row["izlazak_na_teren"], $row["majstor_hint_pitanje"], $row["majstor_hint_odgovor"], $row["dostupnost_za_rad"], $row["online"]);
            $zanati = $this->vratiZanateMajstora($majstor->majstor_Email);
            $majstor->dodajSveZanate($zanati);
            $lokacije = $this->vratiGradoveMajstora($majstor->majstor_Email);
            // var_dump($lokacije);
            $majstor->dodajSveLokacije($lokacije);
            $majstor->ocena = $this->prosecnaOcena($majstor->majstor_Email);
            $majstori[] = $majstor;
        }

        return $majstori;
    }

    public function vratiPorukeZaAdmina() {

        $collection = (new MongoDB\Client)->mongo_baza->razmena_poruka;
        $cursor = $collection->find(['majstor_Email0' => 'admin']);
        $poruke = array();
        $poruka = null;
        foreach ($cursor as $document) {

            $poruka = $document["musterija_Email1"];
            if (!in_array($poruka, $poruke))
                $poruke[] = $poruka;
        }

        $cursor = $collection->find(['musterija_Email1' => 'admin']);
        foreach ($cursor as $document) {

            $poruka = $document["majstor_Email0"];
            if (!in_array($poruka, $poruke))
                $poruke[] = $poruka;
        }

        return $poruke;
    }

    function vrati_sve_fajlove_za_prikaz() {

        $collection = (new MongoDB\Client)->mongo_baza->fajl_u_bazi;
        $cursor = $collection->find([]);
        $niz = array();

        foreach ($cursor as $document) {

            $niz[$document['id']] = new slika($document['id'], $document['naziv'], $document['tip'], $document['velicina'], $document['redniBroj'], $document['putanja']);
        }

        return $niz;
    }

    function vrati_fajl_za_preuzimanje($email) {

        $collection = (new MongoDB\Client)->mongo_baza->fajl_u_bazi;
        $cursor = $collection->find(['email_korisnika' => $email]);
        $n = NULL;


        foreach ($cursor as $document) {
            $n = new slika($document['id'], $document['naziv'], $document['tip'], $document['velicina'], $document['redniBroj'], $document['putanja']);
        }
        $fp = fopen($n->putanja, "r");
        if ($fp) {
            $n->sadrzaj = fread($fp, $toReturn->velicina);
            fclose($fp);
        }

        return $n;
    }

    function dodaj_fajl(slika $fajl, $email) {

        $collection = (new MongoDB\Client)->mongo_baza->fajl_u_bazi;

        $cursor = $collection->find(
                [], [
            'limit' => 1,
            'sort' => ['id' => -1],
                ]
        );
        $a = 0;
        foreach ($cursor as $c) {
            $a = $c['id'];
        }
        $a = $a + 1;
        $insertOneResult = $collection->insertOne(['id' => $a, 'naziv' => $fajl->naziv, 'tip' => $fajl->tip, 'velicina' => $fajl->velicina, 'putanja' => $fajl->putanja,
            'redni_broj' => $fajl->redniBroj, 'email_korisnika' => $email]);
        return true;
    }

    function vratiSlikuKorisnika($email) {


        $collection = (new MongoDB\Client)->mongo_baza->fajl_u_bazi;
        $cursor = $collection->find(['email_korisnika' => $email]);
        $n = NULL;


        foreach ($cursor as $document) {
            $n = $document['putanja'];
        }
        return $n;
    }

    public function vratiSifruMusterija($email) {

        $collection = (new MongoDB\Client)->mongo_baza->musterija;
        $document = $collection->findOne(['musterija_Email' => $email]);
        return $document['password'];
    }

    public function vratiSifruMajstor($email) {

        $collection = (new MongoDB\Client)->mongo_baza->majstor;
        $document = $collection->findOne(['majstor_Email' => $email]);
        return $document['password'];
    }

    public function vratiHintOdgovorMusterija($email) {

        $collection = (new MongoDB\Client)->mongo_baza->musterija;
        $document = $collection->findOne(['musterija_Email' => $email]);
        return $document['musterija_hint_odgovor'];
    }

    public function vratiHintOdgovorMajstor($email) {

        $collection = (new MongoDB\Client)->mongo_baza->majstor;
        $document = $collection->findOne(['majstor_Email' => $email]);
        return $document['majstor_hint_odgovor'];
    }

    public function vratiHintPitanjeMajstor($email) {

        $collection = (new MongoDB\Client)->mongo_baza->majstor;
        $document = $collection->findOne(['majstor_Email' => $email]);
        return $document['majstor_hint_pitanje'];
    }

    public function vratiHintPitanjeMusterija($email) {

        $collection = (new MongoDB\Client)->mongo_baza->musterija;
        $document = $collection->findOne(['musterija_Email' => $email]);
        return $document['musterija_hint_pitanje'];
    }

    public function vratiPrijavljeneOglaseZaMajstora($emailM) {

        $collection = (new MongoDB\Client)->mongo_baza->prijava_za_posao;
        $cursor = $collection->find(['majstor_Email3' => $emailM]);


        $idOglasa = "";
        $oglas = null;
        $oglasi = array();

        foreach ($cursor as $document) {

            $idOglasa = $document["idOglasa"];
            $oglas = $this->vratiOglasPoId($idOglasa);
            $oglasi[$idOglasa] = $oglas;
        }
        //sortirati u opadajuci jedino
        return $oglasi;
    }

    public function otkaziPrijavuMajstora($emailMajstora, $id) {

        $collection = (new MongoDB\Client)->mongo_baza->prijava_za_posao;
        $deleteResult = $collection->deleteOne(['majstor_Email3' => $emailMajstora, 'idOglasa' => $id]);
    }

//    public function vratiRazgovoreZaMajstora($email) {
//        $collection = (new MongoDB\Client)->mongo_baza->razmena_poruka;
//        $cursor = $collection->find(['majstor_Email0' => $email], ['sort' => ['datum_slanja_poruke' => -1]]);
//
//        $poruke = array();
//        $poruka = null;
//
//        if ($cursor) {
//            foreach ($cursor as $message) {
//                $poruka = new Poruka($message["majstor_Email0"], $message["musterija_Email1"], $message["tekst_poruke"], $message["datum_slanja_poruke"], $message["posiljaoc"]);
//                $poruke[] = $poruka;
//            };
//
//            return $poruke;
//        }
//        /* SELECT * 
//          FROM razmena_poruka
//          WHERE majstor_Email0='$email' GROUP BY musterija_Email1 ORDER BY datum_slanja_poruke DESC */
//    }
    public function vratiRazgovoreZaMajstora($email) {
        $collection = (new MongoDB\Client)->mongo_baza->razmena_poruka;
        //$cursor = $collection->aggregate(['majstor_Email0' => $email], ['sort' => ['datum_slanja_poruke' => -1]]);
        
        
	$cursor = $collection->aggregate([['$match' => ['majstor_Email0' => $email]], ['$group' => ['_id' => '$musterija_Email1']], ['$sort' =>['datum_slanja_poruke' => -1]]]);

                
        
        $poruke = array();
        $poruka = null;

        if ($cursor) {
            
            foreach ($cursor as $message) {
                //var_dump($message);
                //$poruka = new Poruka($message["majstor_Email0"], $message["musterija_Email1"], $message["tekst_poruke"], $message["datum_slanja_poruke"], $message["posiljaoc"]);
                $poruka = new Poruka($email, $message["_id"], "", "", "");
                //var_dump($poruka);
                $poruke[] = $poruka;
            };

            //var_dump($poruke);
            return $poruke;
        }
        /* SELECT * 
          FROM razmena_poruka
          WHERE majstor_Email0='$email' GROUP BY musterija_Email1 ORDER BY datum_slanja_poruke DESC */
    }


    public function posaljiPoruku(Poruka $p) {
        $collection = (new MongoDB\Client)->mongo_baza->razmena_poruka;
        
        $cursor = $collection->findOne([], ['sort' => ['id' => -1]]);
        
        
        $maxId = $cursor['id'];
        $maxId1 = (int)$maxId + 1;
        
        $date = date("Y-m-d H:i:s");
        $cursor = $collection->insertOne(['id' => $maxId1, 'majstor_Email0' => $p->majstor_email0, 'musterija_Email1' => $p->musterija_email1, 'tekst_poruke' => $p->tekst_poruke, 'datum_slanja_poruke' => $date, 'posiljaoc' => $p->posiljaoc]);

        return true;
    }

    public function obrisiZanateMajstora($email) {
        $collection = (new MongoDB\Client)->mongo_baza->poseduje_zanate;
        $cursor = $collection->deleteOne(['majstor_Email0' => $email]);

        return true;
    }

    public function updateZanateMajstora($email, $stariZanat, $noviZanat) {
        $collection = (new MongoDB\Client)->mongo_baza->poseduje_zanate;
        $cursor = $collection->updateOne(['majstor_Email0' => $email,
            'tip1' => $stariZanat], ['$set' => ['tip1' => $noviZanat]]);

        return true;
    }

    public function upisiZanateMajstora($email, $zanat) {
        $collection = (new MongoDB\Client)->mongo_baza->poseduje_zanate;
        
        $cursor = $collection->findOne([], ['sort' => ['id' => -1]]);
        
        $maxId = $cursor['id'];
        $maxId1 = (int)$maxId + 1;
        
        $cursor = $collection->insertOne(['id' => $maxId1, 'majstor_Email0' => $email, 'tip1' => $zanat]);

        return true;
    }

    public function daLiJeAdmin($email) {
        $collection = (new MongoDB\Client)->mongo_baza->admin;
        $cursor = $collection->findOne(['imejl' => $email]);

        if ($cursor['imejl'] == $email)
            return true;
        return false;
    }

    public function daLiJeMajstor($email) {
        $collection = (new MongoDB\Client)->mongo_baza->majstor;
        $cursor = $collection->findOne(['majstor_Email' => $email]);

        if ($cursor['majstor_Email'] == $email)
            return true;
        return false;
    }

    public function daLiJeMusterija($email) {
        $collection = (new MongoDB\Client)->mongo_baza->musterija;
        $cursor = $collection->findOne(['musterija_Email' => $email]);

        if ($cursor['musterija_Email'] == $email)
            return true;
        return false;
    }

    public function logAdmina($email, $pas) {
        $collection = (new MongoDB\Client)->mongo_baza->admin;
        $cursor = $collection->findOne(['imejl' => $email, 'lozinka' => $pas]);

        if ($cursor['imejl'] == $email)
            return true;
        return false;
    }

    public function logMajstor($email, $pas) {
        $collection = (new MongoDB\Client)->mongo_baza->majstor;
        $cursor = $collection->findOne(['majstor_Email' => $email, 'password' => $pas]);

        if ($cursor['majstor_Email'] == $email)
            return true;
        return false;
    }

    public function logMusterija($email, $pas) {
        $collection = (new MongoDB\Client)->mongo_baza->musterija;
        $cursor = $collection->findOne(['musterija_Email' => $email, 'password' => $pas]);

        if ($cursor['musterija_Email'] == $email)
            return true;
        return false;
    }

    public function vratiMajstorePoKriterijumima($lokacija, $kategorija, $izlazakNaTeren) {
        $collection1 = (new MongoDB\Client)->mongo_baza->majstor;
        $cursor1 = $collection1->find(['izlazak_na_teren' => $izlazakNaTeren]);

        $collection2 = (new MongoDB\Client)->mongo_baza->radi_na;
        $cursor2 = $collection2->find(['mesto1' => $lokacija]);

        $collection3 = (new MongoDB\Client)->mongo_baza->poseduje_zanate;
        $cursor3 = $collection3->find(['tip1' => $kategorija]);

        $cursor1 = $cursor1->toArray();
        $cursor2 = $cursor2->toArray();
        $cursor3 = $cursor3->toArray();
        if ($cursor1 && $cursor2 && $cursor3) {
            $majstor_radiNa = array();

            foreach ($cursor1 as $majstor) {
                foreach ($cursor2 as $radiNa)
                    if ($majstor['majstor_Email'] == $radiNa['majstor_Email0']) {
                        $majstor_radiNa[] = $majstor['majstor_Email'];
                    }
            }

            $majstori = array();

            foreach ($cursor3 as $zanat)
                foreach ($majstor_radiNa as $mr)
                    if ($mr == $zanat['majstor_Email0'])
                        $majstori[] = $this->vratiKompletnoMajstor($mr);

            return $majstori;
        }

        return false;
    }

    public function upisiGradoveZaMajstora($email, $grad) {
        $collection = (new MongoDB\Client)->mongo_baza->radi_na;
        
        $cursor = $collection->findOne([], ['sort' => ['id' => -1]]);
        
        $maxId = $cursor['id'];
        $maxId1 = (int)$maxId + 1;
        
        $collection->insertOne(['id' => $maxId1, 'majstor_Email0' => $email, 'mesto1' => $grad]);

        return true;
    }

    public function updateGradoveMajstora($email, $stariGrad, $noviGrad) {
        $collection = (new MongoDB\Client)->mongo_baza->radi_na;
        $collection->updateOne(['majstor_Email0' => $email,
            'mesto1' => $stariGrad], ['$set' => ['mesto1' => $noviGrad]]);

        return true;
    }

    public function obrisiGradoveMajstora($email) {
        $collection = (new MongoDB\Client)->mongo_baza->radi_na;
        $cursor = $collection->deleteOne(['majstor_Email0' => $email]);

        return true;
    }

    public function vratiPoImenu($ime, $prezime) {
        $collection = (new MongoDB\Client)->mongo_baza->majstor;
        $cursor = $collection->find(['ime' => $ime, 'prezime' => $prezime]);

        if ($cursor) {
            $majstori = array();

            foreach ($cursor as $majstor) {
                $m = new Majstor($majstor["majstor_Email"], $majstor["password"], $majstor["ime"], $majstor["prezime"], $majstor["kontakt"], $majstor["kvalifikacije"], $majstor["adresa"], $majstor["vreme_radni_dan"], $majstor["vreme_subota"], $majstor["vreme_nedelja"], $majstor["izlazak_na_teren"], $majstor["majstor_hint_pitanje"], $majstor["majstor_hint_odgovor"], $majstor["dostupnost_za_rad"], $majstor["online"]);
                //$majstori[]=$m;

                $zanati = $this->vratiZanateMajstora($m->majstor_Email);
                $m->dodajSveZanate($zanati);
                $lokacije = $this->vratiGradoveMajstora($m->majstor_Email);
                $m->dodajSveLokacije($lokacije);
                $m->ocena = $this->prosecnaOcena($m->majstor_Email);
                $majstori[] = $m;
            };

            return $majstori;
        }

        return false;
    }

    public function vratiKategorije() {
        ///UPOZORENJE: FUNKCIJA SE ZOVE vratiKategorije A VRACA LOKACIJE
        $collection = (new MongoDB\Client)->mongo_baza->lokacija;
        $cursor = $collection->find();

        if ($cursor) {
            $lokacije = array();

            foreach ($cursor as $lok)
                $lokacija[] = new Lokacija($lok['mesto']);

            return lokacije;
        }
        return false;
    }

    public function dodajNoviOglas(Oglas $o) {
        $collection = (new MongoDB\Client)->mongo_baza->oglas;
        
        $cursor = $collection->findOne([], ['sort' => ['_id' => -1]]);
        
        $maxId1 = $cursor['id'] . $cursor['_id'];
        
        $cursor = $collection->insertOne(['id' => $maxId1, 'tekst_oglasa' => $o->tekst_oglasa, 'datum' => $o->datum, 'adresa' => $o->adresa, 'vrsta_posla_zanata' => $o->vrsta_posla, 'musterija_Email5' => $o->vlasnik, 'mesto6' => $o->mesto, 'majstor_koji_radi_email' => 0]);

        return true;
    }

    public function vratiKomentare($email) {
        $collection = (new MongoDB\Client)->mongo_baza->ostavljanje_komentara;
        $cursor = $collection->find(['majstor_Email1' => $email]);

        if ($cursor) {
            $komentari = array();

            foreach ($cursor as $komentar)
                $komentari[] = new Komentar($komentar['musterija_Email0'], $komentar['komentar_poz_tekst'], $komentar['komentar_neg_tekst']);

            //var_dump($komentari);
            return $komentari;
        }
        return false;
    }

    public function dodajKomentar(Komentar $k, $emailM) {
        $collection = (new MongoDB\Client)->mongo_baza->ostavljanje_komentara;
        
        $cursor = $collection->findOne([], ['sort' => ['id' => -1]]);
        
        $maxId = $cursor['id'];
        $maxId1 = (int)$maxId + 1;
        
        $cursor = $collection->insertOne(['id' => $maxId1, 'musterija_Email0' => $k->posiljaoc, 'majstor_Email1' => $emailM, 'komentar_poz_tekst' => $k->komentar_tekst_pozitivan, 'komentar_neg_tekst' => $k->komentar_tekst_negativan]);

        return true;
    }

    public function proveriDaLiJeOcenio($musterija, $majstor) {
        $collection = (new MongoDB\Client)->mongo_baza->ocenjuje;
        $cursor = $collection->findOne(['majstor_Email0' => $majstor, 'musterija_Email1' => $musterija]);

        if ($cursor['majstor_Email0'] == $majstor)
            return true;
        return false;
    }

    public function prijaviMajstoraZaPosao($emailMajstora, $idOglasa, Oglas $oglas) {
        $collection = (new MongoDB\Client)->mongo_baza->prijava_za_posao;
        //$cursor = $collection->findOne([], ['sort' => ['id' => -1]]);
        
        /*$maxId = $cursor['id'];
        $maxId1 = (int)$maxId + 1;*/
          $cursor = $collection->findOne([], ['sort' => ['_id' => -1]]);
        
        $maxId1 = $cursor['id'] . $cursor['_id'];
        
        $cursor = $collection->insertOne(['id' => $maxId1, 'musterija_Email5' => $oglas->vlasnik, 'datum0' => $oglas->datum, 'adresa1' => $oglas->adresa, 'vrsta_posla_zanata2' => $oglas->vrsta_posla, 'majstor_Email3' => $emailMajstora, 'idOglasa' => $idOglasa]);
        
        return true;
    }

    public function prikaziPrijavljeneMajstore($idOglasa) {
        $collection = (new MongoDB\Client)->mongo_baza->prijava_za_posao;
        $cursor = $collection->find(['idOglasa' => $idOglasa]);

        if ($cursor) {
            $majstori = array();

            foreach ($cursor as $m)
                $majstori[] = $this->vratiKompletnoMajstor($m['majstor_Email3']);

            return $majstori;
        }

        return false;
    }

    public function dodajMajstoraKojiRadi($emailMajstor, $idOglasa) {
        $collection = (new MongoDB\Client)->mongo_baza->oglas;
        $cursor = $collection->updateOne(['id' => $idOglasa], ['$set' => ['majstor_koji_radi_email' => $emailMajstor]]);

        return true;
    }

    public function prijaveZaPosaoMajstora($emailM) {
        $collection = (new MongoDB\Client)->mongo_baza->prijava_za_posao;
        $cursor1 = $collection->find(['majstor_Email3' => $emailM]);

        $collection = (new MongoDB\Client)->mongo_baza->oglas;
        $cursor2 = $collection->find([], ['sort' => ['datum' => -1]]);

        if ($cursor1 && $cursor2) {
            $oglasi = array();

            foreach ($cursor2 as $o)
                foreach ($cursor1 as $p)
                    if ($o['id'] == $p['idOglasa'])
                        $oglasi[$p['idOglasa']] = $this->vratiOglasPoId($p["idOglasa"]);

            return $oglasi;
        }

        return false;
    }

    public function daLiJeMajstorPrijavljenZaOglas($emailM, $idOglasa) {
        $collection = (new MongoDB\Client)->mongo_baza->prijava_za_posao;
        $cursor = $collection->findOne(['majstor_Email3' => $emailM, 'idOglasa' => $idOglasa]);

        if ($cursor['majstor_Email3'] == $emailM)
            return true;

        return false;
    }

}
