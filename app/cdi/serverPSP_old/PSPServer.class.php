<?php
    require_once "../logWriter.class.php";
    require_once "../Helper.php";
    require_once "../DOMValidator.class.php";
    require_once "../dbms.class.php";
    require_once "../RT.class.php";
    require_once "../RR.class.php";
    require_once "../RTRequest.class.php";
    require_once "../RRRequest.class.php";

    class PSPServer
    {

        /* Se è true viene automaticamente inviata la revoca del pagamento */
        /** @var $inviaREVOCA bool  */
        private $inviaREVOCA = false;


        function pspInviaRPT()
        {
            $soapResponse = new StdClass;
            $esito="OK";
            $soapResponse->pspInviaRPTResponse->esitoComplessivoOperazione = $esito;
            $soapMessage = new SoapVar($soapResponse, SOAP_ENC_OBJECT);

            /////
            $soapRequest = file_get_contents ('php://input');
            $content["request"] = $soapRequest;
            $content["response"] = json_encode($soapResponse);
            $this->logSmallRequest1($content);
            /////

            /////
            $command = "curl --insecure https://pagopatest.agid.gov.it/cdi/serverPSP/PSPServerCoda.php";
            shell_exec( $command . "> /dev/null 2>/dev/null &" );
            /////

            return $soapMessage;

        }

        function pspInviaCarrelloRPT()
        {

            $soapResponse = new StdClass;
            $esito="OK";
            $soapResponse->pspInviaCarrelloRPTResponse->esitoComplessivoOperazione = $esito;
            $soapResponse->pspInviaCarrelloRPTResponse->parametriPagamentoImmediato = "idRichiesta=717bdafa-c111-47ec-83d5-53".rand ( 10000 , 99999 );
            $soapMessage = new SoapVar($soapResponse, SOAP_ENC_OBJECT);

            /////
            $soapRequest = file_get_contents ('php://input');
            $content["request"] = $soapRequest;
            $content["response"] = json_encode($soapResponse);
            $this->logSmallRequest2($content);
            /////

            return $soapMessage;

        }

        function pspInviaCarrelloRPTCarte()
        {

            $soapResponse = new StdClass;
            $esito="OK";
            $soapResponse->pspInviaCarrelloRPTResponse->esitoComplessivoOperazione = $esito;
            $soapMessage = new SoapVar($soapResponse, SOAP_ENC_OBJECT);

            $soapRequest = file_get_contents ('php://input');
            //Response
            $soapRequestX = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $soapRequest);
            $soapRequestXML = simplexml_load_string($soapRequestX);

            $numRPT=0;
            foreach($soapRequestXML->soapenvBody->pptpspInviaCarrelloRPTCarte->listaRPT->elementoListaCarrelloRPT as $item)
            {
                $numRPT+=1;
                $identificativoDominio = (string)$item->identificativoDominio;
                $identificativoUnivocoVersamento = (string)$item->identificativoUnivocoVersamento;
                $codiceContestoPagamento = (string)$item->codiceContestoPagamento;
                $encodedRPT = (string)$item->rpt;

                $decodedRPT = base64_decode($encodedRPT);
                $decodedRPTXML = simplexml_load_string($decodedRPT);

                //Log Received RPT
                $content["RPT" . $numRPT] = $decodedRPT;
                $content["identificativoUnivocoVersamento" . $numRPT] = $identificativoUnivocoVersamento;
                $content["codiceContestoPagamento" . $numRPT] = $codiceContestoPagamento;

                ///////////////////////////
                //Sending RT
                sleep(2);
                $decodedRPT = base64_decode($encodedRPT);
                $decodedRPTXML = simplexml_load_string($decodedRPT);

                //Log
                $content["RPT" . $numRPT] = $decodedRPT;
                $content["identificativoUnivocoVersamento" . $numRPT] = $identificativoUnivocoVersamento;
                $content["codiceContestoPagamento" . $numRPT] = $codiceContestoPagamento;

                //Response
                $length=15;
                $idMessaggio = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'),1,$length);
                $numPagamenti = 0;
                $datiVersamento = array();

                foreach($decodedRPTXML->datiVersamento->datiSingoloVersamento as $item) {
                    $numPagamenti += 1;
                    $datiVersamento["singoloImportoPagato" . $numPagamenti]=sprintf('%0.2f', (float) $item->importoSingoloVersamento);
                    $datiVersamento["esitoSingoloPagamento" . $numPagamenti]="OK";
                    $datiVersamento["dataEsitoSingoloPagamento" . $numPagamenti]=$decodedRPTXML->datiVersamento->dataEsecuzionePagamento;
                    $datiVersamento["identificativoUnivocoRiscossione" . $numPagamenti]=rand(10,99999);
                    $datiVersamento["causaleVersamento" . $numPagamenti]=$item->causaleVersamento;
                    $datiVersamento["datiSpecificiRiscossione" . $numPagamenti]=$item->datiSpecificiRiscossione;
                }
                $numPagamenti += 1;
                for($d=$numPagamenti; $d<6; $d++) {
                    $datiVersamento["singoloImportoPagato" . $d]=0;
                    $datiVersamento["esitoSingoloPagamento" . $d]=0;
                    $datiVersamento["dataEsitoSingoloPagamento" . $d]=0;
                    $datiVersamento["identificativoUnivocoRiscossione" . $d]=0;
                    $datiVersamento["causaleVersamento" . $d]=0;
                    $datiVersamento["datiSpecificiRiscossione" . $d]=0;
                }

                /***********************************/
                //RT class
                $RTsettings = array(
                    'versioneOggetto' => $decodedRPTXML->versioneOggetto,
                    'identificativoDominio' => '77777777777',
                    'identificativoMessaggioRicevuta' => $idMessaggio,
                    'dataOraMessaggioRicevuta' => date("c"),
                    'riferimentoMessaggioRichiesta' => $decodedRPTXML->identificativoMessaggioRichiesta,
                    'riferimentoDataRichiesta' => date("Y-m-d", strtotime($decodedRPTXML->dataOraMessaggioRichiesta)),

                    'tipoIdentificativoUnivocoAttestante' => 'G',
                    'codiceIdentificativoUnivocoAttestante' => '97735020584',
                    'denominazioneAttestante' => "Agenzia per l'Italia Digitale",
                    'codiceUnitOperAttestante' => 'n/a',
                    'denomUnitOperAttestante' => 'n/a',
                    'indirizzoAttestante' => 'Via Liszt',
                    'civicoAttestante' => '21',
                    'capAttestante' => '00144',
                    'localitaAttestante' => 'Roma',
                    'provinciaAttestante' => 'RM',
                    'nazioneAttestante' => 'IT',

                    'tipoIdentificativoUnivocoBeneficiario' => $decodedRPTXML->enteBeneficiario->identificativoUnivocoBeneficiario->tipoIdentificativoUnivoco,
                    'codiceIdentificativoUnivocoBeneficiario' => $decodedRPTXML->enteBeneficiario->identificativoUnivocoBeneficiario->codiceIdentificativoUnivoco,
                    'denominazioneBeneficiario' => $decodedRPTXML->enteBeneficiario->denominazioneBeneficiario,
                    'indirizzoBeneficiario' => $decodedRPTXML->enteBeneficiario->indirizzoBeneficiario,
                    'civicoBeneficiario' => $decodedRPTXML->enteBeneficiario->civicoBeneficiario,
                    'capBeneficiario' => $decodedRPTXML->enteBeneficiario->capBeneficiario,
                    'localitaBeneficiario' => $decodedRPTXML->enteBeneficiario->localitaBeneficiario,
                    'provinciaBeneficiario' => $decodedRPTXML->enteBeneficiario->provinciaBeneficiario,
                    'nazioneBeneficiario' => $decodedRPTXML->enteBeneficiario->nazioneBeneficiario,

                    'tipoIdentificativoUnivocoVersante' => $decodedRPTXML->soggettoVersante->identificativoUnivocoVersante->tipoIdentificativoUnivoco,
                    'codiceIdentificativoUnivocoVersante' => $decodedRPTXML->soggettoVersante->identificativoUnivocoVersante->codiceIdentificativoUnivoco,
                    'anagraficaVersante' => $decodedRPTXML->soggettoVersante->anagraficaVersante,
                    'indirizzoVersante' => $decodedRPTXML->soggettoVersante->indirizzoVersante,
                    'civicoVersante' => $decodedRPTXML->soggettoVersante->civicoVersante,
                    'capVersante' => $decodedRPTXML->soggettoVersante->capVersante,
                    'localitaVersante' => $decodedRPTXML->soggettoVersante->localitaVersante,
                    'provinciaVersante' => $decodedRPTXML->soggettoVersante->provinciaVersante,
                    'nazioneVersante' => $decodedRPTXML->soggettoVersante->nazioneVersante,
                    'e-mailVersante' => "daniele.landro@agid.gov.it",

                    'tipoIdentificativoUnivocoPagatore' => $decodedRPTXML->soggettoPagatore->identificativoUnivocoPagatore->tipoIdentificativoUnivoco,
                    'codiceIdentificativoUnivocoPagatore' => $decodedRPTXML->soggettoPagatore->identificativoUnivocoPagatore->codiceIdentificativoUnivoco,
                    'anagraficaPagatore' => $decodedRPTXML->soggettoPagatore->anagraficaPagatore,
                    'indirizzoPagatore' => $decodedRPTXML->soggettoPagatore->indirizzoPagatore,
                    'civicoPagatore' => $decodedRPTXML->soggettoPagatore->civicoPagatore,
                    'capPagatore' => $decodedRPTXML->soggettoPagatore->capPagatore,
                    'localitaPagatore' => $decodedRPTXML->soggettoPagatore->localitaPagatore,
                    'provinciaPagatore' => $decodedRPTXML->soggettoPagatore->provinciaPagatore,
                    'nazionePagatore' => $decodedRPTXML->soggettoPagatore->nazionePagatore,
                    'e-mailPagatore' => "daniele.landro@agid.gov.it",


                    'codiceEsitoPagamento' => 0,
                    'importoTotalePagato' => (float) $decodedRPTXML->datiVersamento->importoTotaleDaVersare,
                    'identificativoUnivocoVersamento' => $decodedRPTXML->datiVersamento->identificativoUnivocoVersamento,
                    'codiceContestoPagamento' => $decodedRPTXML->datiVersamento->codiceContestoPagamento,

                    'singoloImportoPagato1' => (float) $datiVersamento["singoloImportoPagato1"],
                    'esitoSingoloPagamento1' => $datiVersamento["esitoSingoloPagamento1"],
                    'dataEsitoSingoloPagamento1' => $datiVersamento["dataEsitoSingoloPagamento1"],
                    'identificativoUnivocoRiscossione1' => $datiVersamento["identificativoUnivocoRiscossione1"],
                    'causaleVersamento1' => $datiVersamento["causaleVersamento1"],
                    'datiSpecificiRiscossione1' => $datiVersamento["datiSpecificiRiscossione1"],
                    'singoloImportoPagato2' => (float) $datiVersamento["singoloImportoPagato2"],
                    'esitoSingoloPagamento2' => $datiVersamento["esitoSingoloPagamento2"],
                    'dataEsitoSingoloPagamento2' => $datiVersamento["dataEsitoSingoloPagamento2"],
                    'identificativoUnivocoRiscossione2' => $datiVersamento["identificativoUnivocoRiscossione2"],
                    'causaleVersamento2' => $datiVersamento["causaleVersamento2"],
                    'datiSpecificiRiscossione2' => $datiVersamento["datiSpecificiRiscossione2"],
                    'singoloImportoPagato3' => (float) $datiVersamento["singoloImportoPagato3"],
                    'esitoSingoloPagamento3' => $datiVersamento["esitoSingoloPagamento3"],
                    'dataEsitoSingoloPagamento3' => $datiVersamento["dataEsitoSingoloPagamento3"],
                    'identificativoUnivocoRiscossione3' => $datiVersamento["identificativoUnivocoRiscossione3"],
                    'causaleVersamento3' => $datiVersamento["causaleVersamento3"],
                    'datiSpecificiRiscossione3' => $datiVersamento["datiSpecificiRiscossione3"],
                    'singoloImportoPagato4' => (float) $datiVersamento["singoloImportoPagato4"],
                    'esitoSingoloPagamento4' => $datiVersamento["esitoSingoloPagamento4"],
                    'dataEsitoSingoloPagamento4' => $datiVersamento["dataEsitoSingoloPagamento4"],
                    'identificativoUnivocoRiscossione4' => $datiVersamento["identificativoUnivocoRiscossione4"],
                    'causaleVersamento4' => $datiVersamento["causaleVersamento4"],
                    'datiSpecificiRiscossione4' => $datiVersamento["datiSpecificiRiscossione4"],
                    'singoloImportoPagato5' => (float) $datiVersamento["singoloImportoPagato5"],
                    'esitoSingoloPagamento5' => $datiVersamento["esitoSingoloPagamento5"],
                    'dataEsitoSingoloPagamento5' => $datiVersamento["dataEsitoSingoloPagamento5"],
                    'identificativoUnivocoRiscossione5' => $datiVersamento["identificativoUnivocoRiscossione5"],
                    'causaleVersamento5' => $datiVersamento["causaleVersamento5"],
                    'datiSpecificiRiscossione5' => $datiVersamento["datiSpecificiRiscossione5"],

                    'dropdownNumPagamenti' => $decodedRPTXML->dropdownNumVersamenti
                );


                //RR class
                $RRsettings = array(
                    'versioneOggetto' => $decodedRPTXML->versioneOggetto,
                    'identificativoDominio' => '77777777777',
                    'identificativoMessaggioRicevuta' => $idMessaggio,
                    'identificativoStazioneRichiedente' => '97735020584_01',  // verificare
                    'dataOraMessaggioRevoca' => date("c"),


                    'tipoIdentificativoUnivocoAttestante' => 'G',
                    'codiceIdentificativoUnivocoAttestante' => '97735020584',
                    'denominazioneAttestante' => "Agenzia per l'Italia Digitale",
                    'codiceUnitOperAttestante' => 'n/a',
                    'denomUnitOperAttestante' => 'n/a',
                    'indirizzoAttestante' => 'Via Liszt',
                    'civicoAttestante' => '21',
                    'capAttestante' => '00144',
                    'localitaAttestante' => 'Roma',
                    'provinciaAttestante' => 'RM',
                    'nazioneAttestante' => 'IT',

                    'tipoIdentificativoUnivocoVersante' => $decodedRPTXML->soggettoVersante->identificativoUnivocoVersante->tipoIdentificativoUnivoco,
                    'codiceIdentificativoUnivocoVersante' => $decodedRPTXML->soggettoVersante->identificativoUnivocoVersante->codiceIdentificativoUnivoco,
                    'anagraficaVersante' => $decodedRPTXML->soggettoVersante->anagraficaVersante,
                    'indirizzoVersante' => $decodedRPTXML->soggettoVersante->indirizzoVersante,
                    'civicoVersante' => $decodedRPTXML->soggettoVersante->civicoVersante,
                    'capVersante' => $decodedRPTXML->soggettoVersante->capVersante,
                    'localitaVersante' => $decodedRPTXML->soggettoVersante->localitaVersante,
                    'provinciaVersante' => $decodedRPTXML->soggettoVersante->provinciaVersante,
                    'nazioneVersante' => $decodedRPTXML->soggettoVersante->nazioneVersante,
                    'e-mailVersante' => "daniele.landro@agid.gov.it",

                    'tipoIdentificativoUnivocoPagatore' => $decodedRPTXML->soggettoPagatore->identificativoUnivocoPagatore->tipoIdentificativoUnivoco,
                    'codiceIdentificativoUnivocoPagatore' => $decodedRPTXML->soggettoPagatore->identificativoUnivocoPagatore->codiceIdentificativoUnivoco,
                    'anagraficaPagatore' => $decodedRPTXML->soggettoPagatore->anagraficaPagatore,
                    'indirizzoPagatore' => $decodedRPTXML->soggettoPagatore->indirizzoPagatore,
                    'civicoPagatore' => $decodedRPTXML->soggettoPagatore->civicoPagatore,
                    'capPagatore' => $decodedRPTXML->soggettoPagatore->capPagatore,
                    'localitaPagatore' => $decodedRPTXML->soggettoPagatore->localitaPagatore,
                    'provinciaPagatore' => $decodedRPTXML->soggettoPagatore->provinciaPagatore,
                    'nazionePagatore' => $decodedRPTXML->soggettoPagatore->nazionePagatore,
                    'e-mailPagatore' => "daniele.landro@agid.gov.it",


                    'importoTotaleRevocato' => (float) $decodedRPTXML->datiVersamento->importoTotaleDaVersare,
                    'identificativoUnivocoVersamento' => $decodedRPTXML->datiVersamento->identificativoUnivocoVersamento,
                    'codiceContestoPagamento' => $decodedRPTXML->datiVersamento->codiceContestoPagamento,

                    'singoloImportoRevocato1' => (float) $datiVersamento["singoloImportoPagato1"],
                    'singoloImportoRevocato2' => (float) $datiVersamento["singoloImportoPagato2"],
                    'singoloImportoRevocato3' => (float) $datiVersamento["singoloImportoPagato3"],
                    'singoloImportoRevocato4' => (float) $datiVersamento["singoloImportoPagato4"],
                    'singoloImportoRevocato5' => (float) $datiVersamento["singoloImportoPagato5"],


                    'dropdownNumPagamenti' => $decodedRPTXML->dropdownNumVersamenti
                );

                //Database
                $dblog="";
                try {
                    $db = new dbms();
                    $insert = $db->query('INSERT INTO pspinviacarrellortcoda (rtarray, rrarray, rrrichiesta, rtinviata, identificativoUnivocoVersamento, codiceContestoPagamento) VALUES (?,?,?,?,?,?)',
                        (string)json_encode($RTsettings),
                        (string)json_encode($RRsettings),
                        (($this->inviaREVOCA==true) ? 1 : 0),
                        0,
                        (string)$decodedRPTXML->datiVersamento->identificativoUnivocoVersamento,
                        (string)$decodedRPTXML->datiVersamento->codiceContestoPagamento
                    );


                    $dblog='MYSQL - Esito inserimento pspInviaCarrelloRPTCarte: ' . $insert->affectedRows() . ' record.';
                    $db->close();
                } catch (Exception $e) {
                    $dblog='MYSQL - Errore inserimento pspInviaCarrelloRPTCarte: ' . $e->getMessage();
                }
                /////

                //Log
                $content["request"] = $soapRequest;
                $content["response"] = json_encode($soapResponse);
                $content["dblog"] = $dblog;
                $this->logSmallRequest2($content);
                /////

                ///// Invio RT
                $command = "curl --insecure https://pagopatest.agid.gov.it/cdi/serverPSP/PSPServerCoda2.php";
                shell_exec( $command . "> /dev/null 2>/dev/null &" );
                /////

  /*              try {
                    //RT class
                    $RTsettings = array(
                        'versioneOggetto' => $decodedRPTXML->versioneOggetto,
                        'identificativoDominio' => '77777777777',
                        'identificativoMessaggioRicevuta' => $idMessaggio,
                        'dataOraMessaggioRicevuta' => date("c"),
                        'riferimentoMessaggioRichiesta' => $decodedRPTXML->identificativoMessaggioRichiesta,
                        'riferimentoDataRichiesta' => date("Y-m-d", strtotime($decodedRPTXML->dataOraMessaggioRichiesta)),

                        'tipoIdentificativoUnivocoAttestante' => 'G',
                        'codiceIdentificativoUnivocoAttestante' => '97735020584',
                        'denominazioneAttestante' => "Agenzia per l'Italia Digitale",
                        'codiceUnitOperAttestante' => 'n/a',
                        'denomUnitOperAttestante' => 'n/a',
                        'indirizzoAttestante' => 'Via Liszt',
                        'civicoAttestante' => '21',
                        'capAttestante' => '00144',
                        'localitaAttestante' => 'Roma',
                        'provinciaAttestante' => 'RM',
                        'nazioneAttestante' => 'IT',

                        'tipoIdentificativoUnivocoBeneficiario' => $decodedRPTXML->enteBeneficiario->identificativoUnivocoBeneficiario->tipoIdentificativoUnivoco,
                        'codiceIdentificativoUnivocoBeneficiario' => $decodedRPTXML->enteBeneficiario->identificativoUnivocoBeneficiario->codiceIdentificativoUnivoco,
                        'denominazioneBeneficiario' => $decodedRPTXML->enteBeneficiario->denominazioneBeneficiario,
                        'indirizzoBeneficiario' => $decodedRPTXML->enteBeneficiario->indirizzoBeneficiario,
                        'civicoBeneficiario' => $decodedRPTXML->enteBeneficiario->civicoBeneficiario,
                        'capBeneficiario' => $decodedRPTXML->enteBeneficiario->capBeneficiario,
                        'localitaBeneficiario' => $decodedRPTXML->enteBeneficiario->localitaBeneficiario,
                        'provinciaBeneficiario' => $decodedRPTXML->enteBeneficiario->provinciaBeneficiario,
                        'nazioneBeneficiario' => $decodedRPTXML->enteBeneficiario->nazioneBeneficiario,

                        'tipoIdentificativoUnivocoVersante' => $decodedRPTXML->soggettoVersante->identificativoUnivocoVersante->tipoIdentificativoUnivoco,
                        'codiceIdentificativoUnivocoVersante' => $decodedRPTXML->soggettoVersante->identificativoUnivocoVersante->codiceIdentificativoUnivoco,
                        'anagraficaVersante' => $decodedRPTXML->soggettoVersante->anagraficaVersante,
                        'indirizzoVersante' => $decodedRPTXML->soggettoVersante->indirizzoVersante,
                        'civicoVersante' => $decodedRPTXML->soggettoVersante->civicoVersante,
                        'capVersante' => $decodedRPTXML->soggettoVersante->capVersante,
                        'localitaVersante' => $decodedRPTXML->soggettoVersante->localitaVersante,
                        'provinciaVersante' => $decodedRPTXML->soggettoVersante->provinciaVersante,
                        'nazioneVersante' => $decodedRPTXML->soggettoVersante->nazioneVersante,
                        'e-mailVersante' => "daniele.landro@agid.gov.it",

                        'tipoIdentificativoUnivocoPagatore' => $decodedRPTXML->soggettoPagatore->identificativoUnivocoPagatore->tipoIdentificativoUnivoco,
                        'codiceIdentificativoUnivocoPagatore' => $decodedRPTXML->soggettoPagatore->identificativoUnivocoPagatore->codiceIdentificativoUnivoco,
                        'anagraficaPagatore' => $decodedRPTXML->soggettoPagatore->anagraficaPagatore,
                        'indirizzoPagatore' => $decodedRPTXML->soggettoPagatore->indirizzoPagatore,
                        'civicoPagatore' => $decodedRPTXML->soggettoPagatore->civicoPagatore,
                        'capPagatore' => $decodedRPTXML->soggettoPagatore->capPagatore,
                        'localitaPagatore' => $decodedRPTXML->soggettoPagatore->localitaPagatore,
                        'provinciaPagatore' => $decodedRPTXML->soggettoPagatore->provinciaPagatore,
                        'nazionePagatore' => $decodedRPTXML->soggettoPagatore->nazionePagatore,
                        'e-mailPagatore' => "daniele.landro@agid.gov.it",


                        'codiceEsitoPagamento' => 0,
                        'importoTotalePagato' => (float) $decodedRPTXML->datiVersamento->importoTotaleDaVersare,
                        'identificativoUnivocoVersamento' => $decodedRPTXML->datiVersamento->identificativoUnivocoVersamento,
                        'codiceContestoPagamento' => $decodedRPTXML->datiVersamento->codiceContestoPagamento,

                        'singoloImportoPagato1' => (float) $datiVersamento["singoloImportoPagato1"],
                        'esitoSingoloPagamento1' => $datiVersamento["esitoSingoloPagamento1"],
                        'dataEsitoSingoloPagamento1' => $datiVersamento["dataEsitoSingoloPagamento1"],
                        'identificativoUnivocoRiscossione1' => $datiVersamento["identificativoUnivocoRiscossione1"],
                        'causaleVersamento1' => $datiVersamento["causaleVersamento1"],
                        'datiSpecificiRiscossione1' => $datiVersamento["datiSpecificiRiscossione1"],
                        'singoloImportoPagato2' => (float) $datiVersamento["singoloImportoPagato2"],
                        'esitoSingoloPagamento2' => $datiVersamento["esitoSingoloPagamento2"],
                        'dataEsitoSingoloPagamento2' => $datiVersamento["dataEsitoSingoloPagamento2"],
                        'identificativoUnivocoRiscossione2' => $datiVersamento["identificativoUnivocoRiscossione2"],
                        'causaleVersamento2' => $datiVersamento["causaleVersamento2"],
                        'datiSpecificiRiscossione2' => $datiVersamento["datiSpecificiRiscossione2"],
                        'singoloImportoPagato3' => (float) $datiVersamento["singoloImportoPagato3"],
                        'esitoSingoloPagamento3' => $datiVersamento["esitoSingoloPagamento3"],
                        'dataEsitoSingoloPagamento3' => $datiVersamento["dataEsitoSingoloPagamento3"],
                        'identificativoUnivocoRiscossione3' => $datiVersamento["identificativoUnivocoRiscossione3"],
                        'causaleVersamento3' => $datiVersamento["causaleVersamento3"],
                        'datiSpecificiRiscossione3' => $datiVersamento["datiSpecificiRiscossione3"],
                        'singoloImportoPagato4' => (float) $datiVersamento["singoloImportoPagato4"],
                        'esitoSingoloPagamento4' => $datiVersamento["esitoSingoloPagamento4"],
                        'dataEsitoSingoloPagamento4' => $datiVersamento["dataEsitoSingoloPagamento4"],
                        'identificativoUnivocoRiscossione4' => $datiVersamento["identificativoUnivocoRiscossione4"],
                        'causaleVersamento4' => $datiVersamento["causaleVersamento4"],
                        'datiSpecificiRiscossione4' => $datiVersamento["datiSpecificiRiscossione4"],
                        'singoloImportoPagato5' => (float) $datiVersamento["singoloImportoPagato5"],
                        'esitoSingoloPagamento5' => $datiVersamento["esitoSingoloPagamento5"],
                        'dataEsitoSingoloPagamento5' => $datiVersamento["dataEsitoSingoloPagamento5"],
                        'identificativoUnivocoRiscossione5' => $datiVersamento["identificativoUnivocoRiscossione5"],
                        'causaleVersamento5' => $datiVersamento["causaleVersamento5"],
                        'datiSpecificiRiscossione5' => $datiVersamento["datiSpecificiRiscossione5"],

                        'dropdownNumPagamenti' => $decodedRPTXML->dropdownNumVersamenti
                    );

                    $helper = new Helper;
                    $rtGen = new RT;
                    $rtContent = $rtGen->getXML($RTsettings);
                    $rtEncodedContent = base64_encode($rtContent);

                    //Action nodoInviaRT
                    $endpointURL = "https://gad.test.pagopa.gov.it/openspcoop2/proxy/PA/PROXYPagamentiTelematiciPspNodo";
                    $privKey='/opt/moc-other/pagopatest.agid.gov.it.key';
                    $pubKey='/opt/moc-other/pagopatest.agid.gov.it.crt';
                    $headers = array(
                        'Content-type: text/xml',
                        'SOAPAction: nodoInviaRT',
                    );
                    $RTrequestParams = array(
                        'identificativoIntermediarioPSP' => '97735020584',
                        'identificativoCanale' => '97735020584_04',
                        'password' => 'pwd_AgID',
                        'identificativoPSP' => 'AGID_02',
                        'identificativoDominio' => '77777777777',
                        'identificativoUnivocoVersamento' => $decodedRPTXML->datiVersamento->identificativoUnivocoVersamento,
                        'codiceContestoPagamento' => $decodedRPTXML->datiVersamento->codiceContestoPagamento,
                        'rtEncodedContent' => $rtEncodedContent
                    );
                    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36';

                    $request = new RTRequest();
                    $soapRequest=$request->getXMLInviaNodo($RTrequestParams);

                    $ch = curl_init($endpointURL);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $soapRequest);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_VERBOSE, 1);
                    curl_setopt($ch, CURLOPT_PORT , 443);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_SSLKEY, $privKey);
                    curl_setopt($ch, CURLOPT_SSLCERT, $pubKey);
                    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

                    $RTResponseBody  = curl_exec($ch);
                    $content["RTrequest" . $numRPT]=$helper->formatXmlString($soapRequest);
                    $content["RT" . $numRPT]=$helper->formatXmlString($rtContent);
                    $content["RTresponse" . $numRPT]=$helper->formatXmlString($RTResponseBody);
                    curl_close($ch);

                } catch (Exception $e) {
                    logError($e->getMessage());
                }
*/
                ///////////////////////////
                //Sending RR
  /*              if ($this->inviaREVOCA==true) {
                    sleep(4);
                      try {
                        //RR class
                        $RRsettings = array(
                            'versioneOggetto' => $decodedRPTXML->versioneOggetto,
                            'identificativoDominio' => '77777777777',
                            'identificativoMessaggioRicevuta' => $idMessaggio,
                            'identificativoStazioneRichiedente' => '97735020584_01',  // verificare
                            'dataOraMessaggioRevoca' => date("c"),


                            'tipoIdentificativoUnivocoAttestante' => 'G',
                            'codiceIdentificativoUnivocoAttestante' => '97735020584',
                            'denominazioneAttestante' => "Agenzia per l'Italia Digitale",
                            'codiceUnitOperAttestante' => 'n/a',
                            'denomUnitOperAttestante' => 'n/a',
                            'indirizzoAttestante' => 'Via Liszt',
                            'civicoAttestante' => '21',
                            'capAttestante' => '00144',
                            'localitaAttestante' => 'Roma',
                            'provinciaAttestante' => 'RM',
                            'nazioneAttestante' => 'IT',

                            'tipoIdentificativoUnivocoVersante' => $decodedRPTXML->soggettoVersante->identificativoUnivocoVersante->tipoIdentificativoUnivoco,
                            'codiceIdentificativoUnivocoVersante' => $decodedRPTXML->soggettoVersante->identificativoUnivocoVersante->codiceIdentificativoUnivoco,
                            'anagraficaVersante' => $decodedRPTXML->soggettoVersante->anagraficaVersante,
                            'indirizzoVersante' => $decodedRPTXML->soggettoVersante->indirizzoVersante,
                            'civicoVersante' => $decodedRPTXML->soggettoVersante->civicoVersante,
                            'capVersante' => $decodedRPTXML->soggettoVersante->capVersante,
                            'localitaVersante' => $decodedRPTXML->soggettoVersante->localitaVersante,
                            'provinciaVersante' => $decodedRPTXML->soggettoVersante->provinciaVersante,
                            'nazioneVersante' => $decodedRPTXML->soggettoVersante->nazioneVersante,
                            'e-mailVersante' => "daniele.landro@agid.gov.it",

                            'tipoIdentificativoUnivocoPagatore' => $decodedRPTXML->soggettoPagatore->identificativoUnivocoPagatore->tipoIdentificativoUnivoco,
                            'codiceIdentificativoUnivocoPagatore' => $decodedRPTXML->soggettoPagatore->identificativoUnivocoPagatore->codiceIdentificativoUnivoco,
                            'anagraficaPagatore' => $decodedRPTXML->soggettoPagatore->anagraficaPagatore,
                            'indirizzoPagatore' => $decodedRPTXML->soggettoPagatore->indirizzoPagatore,
                            'civicoPagatore' => $decodedRPTXML->soggettoPagatore->civicoPagatore,
                            'capPagatore' => $decodedRPTXML->soggettoPagatore->capPagatore,
                            'localitaPagatore' => $decodedRPTXML->soggettoPagatore->localitaPagatore,
                            'provinciaPagatore' => $decodedRPTXML->soggettoPagatore->provinciaPagatore,
                            'nazionePagatore' => $decodedRPTXML->soggettoPagatore->nazionePagatore,
                            'e-mailPagatore' => "daniele.landro@agid.gov.it",


                            'importoTotaleRevocato' => (float) $decodedRPTXML->datiVersamento->importoTotaleDaVersare,
                            'identificativoUnivocoVersamento' => $decodedRPTXML->datiVersamento->identificativoUnivocoVersamento,
                            'codiceContestoPagamento' => $decodedRPTXML->datiVersamento->codiceContestoPagamento,

                            'singoloImportoRevocato1' => (float) $datiVersamento["singoloImportoPagato1"],
                            'singoloImportoRevocato2' => (float) $datiVersamento["singoloImportoPagato2"],
                            'singoloImportoRevocato3' => (float) $datiVersamento["singoloImportoPagato3"],
                            'singoloImportoRevocato4' => (float) $datiVersamento["singoloImportoPagato4"],
                            'singoloImportoRevocato5' => (float) $datiVersamento["singoloImportoPagato5"],


                            'dropdownNumPagamenti' => $decodedRPTXML->dropdownNumVersamenti
                        );

                        $rrGen = new RR;
                        $rrContent = $rrGen->getXML($RRsettings);
                        $rrEncodedContent = base64_encode($rrContent);

                        //////////////////////////////////
                        //Action nodoInviaRichiestaRevoca
                        $endpointURL = "https://gad.test.pagopa.gov.it/openspcoop2/proxy/PA/PROXYPagamentiTelematiciPspNodo";
                        $privKey='/opt/moc-other/pagopatest.agid.gov.it.key';
                        $pubKey='/opt/moc-other/pagopatest.agid.gov.it.crt';
                        $headers = array(
                            'Content-type: text/xml',
                            'SOAPAction: nodoInviaRichiestaRevoca',
                        );
                        $RRrequestParams = array(
                            'identificativoIntermediarioPSP' => '97735020584',
                            'identificativoCanale' => '97735020584_04',
                            'password' => 'pwd_AgID',
                            'identificativoPSP' => 'AGID_02',
                            'identificativoDominio' => '77777777777',
                            'identificativoUnivocoVersamento' => $decodedRPTXML->datiVersamento->identificativoUnivocoVersamento,
                            'codiceContestoPagamento' => $decodedRPTXML->datiVersamento->codiceContestoPagamento,
                            'rrEncodedContent' => $rrEncodedContent
                        );
                        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36';

                        $RRrequest = new RRRequest();
                        $RRsoapRequest=$RRrequest->getXMLInviaNodo($RRrequestParams);

                        $ch = curl_init($endpointURL);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $soapRequest);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                        curl_setopt($ch, CURLOPT_PORT , 443);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_SSLKEY, $privKey);
                        curl_setopt($ch, CURLOPT_SSLCERT, $pubKey);
                        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

                        $RRresponseBody  = curl_exec($ch);
                        $content["RRrequest" . $numRPT]=$helper->formatXmlString($RRsoapRequest);
                        $content["RR" . $numRPT]=$helper->formatXmlString($rrContent);
                        $content["RRresponse" . $numRPT]=$helper->formatXmlString($RRresponseBody);
                        curl_close($ch);

                    } catch (Exception $e) {
                        logError($e->getMessage());
                    }

                }  // END RR
*/

            } // foreach elementoListaCarrelloRPT


            $content["request"] = $soapRequest;
            $content["response"] = json_encode($soapResponse);
            $content["numRPT"] = $numRPT;
            $this->logSmallRequest3($content);

            return $soapMessage;

        }

        function pspInviaRispostaRevoca()
        {
            $soapResponse = new StdClass;
            $esito="OK";
            $soapResponse->pspInviaRispostaRevocaResponse->esito = $esito;
            $soapMessage = new SoapVar($soapResponse, SOAP_ENC_OBJECT);

            /////
            $soapRequest = file_get_contents ('php://input');
            $content["request"] = $soapRequest;
            $content["response"] = json_encode($soapResponse);
            $this->logSmallRequest4($content);
            /////

            return $soapMessage;
        }

        function logError($message) {
            //Log
            $time = date('d-M-Y');
            $logPath='../uploads/logs/log-PSPserverError-' . $time . '.txt';
            $log = new logWriter($logPath);
            $helper = new Helper;

            $separator = PHP_EOL .'=================================================================';
            $log->error($separator . PHP_EOL . '=== ERRORE NEL SERVER PSP: ' . PHP_EOL . $helper->formatXmlString($message) );
        }

        function logSmallRequest1($infos) {
            //Log
            $time = date('d-M-Y');
            $logPath='../uploads/logs/log-PSPserver-' . $time . '.txt';
            $log = new logWriter($logPath);
            $helper = new Helper;

            $separator = PHP_EOL .'=================================================================';
            $log->info($separator . PHP_EOL . '=== SERVER PSP pspInviaRPT REQUEST: ' . PHP_EOL . $helper->formatXmlString($infos["request"]) );
            $log->info($separator . PHP_EOL . '=== Risposta del server alla RPT: ' . PHP_EOL . $infos["response"] );
        }

        function logSmallRequest2($infos) {
            //Log
            $time = date('d-M-Y');
            $logPath='../uploads/logs/log-PSPserver-' . $time . '.txt';
            $log = new logWriter($logPath);
            $helper = new Helper;

            $separator = PHP_EOL .'=================================================================';
            $log->info($separator . PHP_EOL . '=== SERVER PSP pspInviaCarrelloRPT REQUEST: ' . PHP_EOL . $helper->formatXmlString($infos["request"]));
            $log->info($separator . PHP_EOL . '=== Risposta del server al carrello RPT: ' . PHP_EOL . $infos["response"] );
            $log->info($separator . PHP_EOL . '=== Esito DB: ' . PHP_EOL . $infos["dblog"] );
        }

        function logSmallRequest3($infos) {
            //Log
            $time = date('d-M-Y');
            $logPath='../uploads/logs/log-PSPserver-' . $time . '.txt';
            $log = new logWriter($logPath);
            $helper = new Helper;

            $separator = PHP_EOL .'=================================================================';
            $log->info($separator . PHP_EOL . '=== SERVER PSP pspInviaCarrelloRPTCarte REQUEST: ' . PHP_EOL . $helper->formatXmlString($infos["request"]));
            $log->info($separator . PHP_EOL . '=== Risposta del server al carrello RPT Carte: ' . PHP_EOL . $infos["response"] );
            $numRPT = $infos["numRPT"];

            //RPT
            for ($i=1; $i<=$numRPT; $i++) {
                $log->info($separator . PHP_EOL
                            . '=== RPT ' . $i . ' di ' . $numRPT . ' ricevuta dal nodo: ' . PHP_EOL
                            . 'IUV: ' . $infos["identificativoUnivocoVersamento" . $i] . PHP_EOL
                            . 'CCP: ' . $infos["codiceContestoPagamento" . $i] . PHP_EOL
                            . 'Contenuto: ' . $helper->formatXmlString($infos["RPT" . $i]) );
            }

            //InviaRT
            for ($i=1; $i<=$numRPT; $i++) {
                $log->info($separator . PHP_EOL . '=== Richiesta RT : ' . PHP_EOL . $infos["RTrequest" . $i] );
                $log->info($separator . PHP_EOL . '=== RT : ' . PHP_EOL . $infos["RT" . $i] );
                $log->info($separator . PHP_EOL . '=== Risposta RT : ' . PHP_EOL . $infos["RTresponse" . $i] );
            }

            //InviaRR
            for ($i=1; $i<=$numRPT; $i++) {
                $log->info($separator . PHP_EOL . '=== Richiesta RR : ' . PHP_EOL . $infos["RRrequest" . $i] );
                $log->info($separator . PHP_EOL . '=== RR : ' . PHP_EOL . $infos["RR" . $i] );
                $log->info($separator . PHP_EOL . '=== Risposta RR : ' . PHP_EOL . $infos["RRresponse" . $i] );
            }
        }

        function logSmallRequest4($infos) {
            //Log
            $time = date('d-M-Y');
            $logPath='../uploads/logs/log-PSPserver-' . $time . '.txt';
            $log = new logWriter($logPath);
            $helper = new Helper;

            $separator = PHP_EOL .'=================================================================';
            $log->info($separator . PHP_EOL . '=== SERVER PSP pspInviaRispostaRevoca REQUEST: ' . PHP_EOL . $helper->formatXmlString($infos["request"]));
            $log->info($separator . PHP_EOL . '=== Risposta del server alla revoca: ' . PHP_EOL . $infos["response"] );
        }

    }
