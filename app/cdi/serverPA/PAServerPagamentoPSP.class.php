<?php
    require_once "../logWriter.class.php";
    require_once "../Helper.php";
    require_once "../DOMValidator.class.php";
    require_once "../dbms.class.php";

    class PAServerPagamentoPSP
    {
        function paaVerificaRPT()
        {
            //Request
            $soapRequest = file_get_contents ('php://input');
            $soapRequestX = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $soapRequest);
            $soapRequestXML = simplexml_load_string($soapRequestX);

            //Response
            $soapResponse = new StdClass;
            $ns="http://www.digitpa.gov.it/schemas/2011/Pagamenti/";

            $esito="OK";
            $soapResponse->paaVerificaRPTRisposta->esito = $esito;

            ///////////////
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->importoSingoloVersamento=129.14;
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->ibanAccredito="IT30N0103076271000001823603";
            $node1 = new SoapVar("G", XSD_STRING, null, null, "tipoIdentificativoUnivoco", $ns);
            $node2 = new SoapVar("00133880252", XSD_STRING, null, null, "codiceIdentificativoUnivoco", $ns);
            $token = new SoapVar(array($node1, $node2), SOAP_ENC_OBJECT, null, null, 'identificativoUnivocoBeneficiario', $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->identificativoUnivocoBeneficiario = new SoapVar($token, SOAP_ENC_OBJECT, null, null, 'identificativoUnivocoBeneficiario', $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->denominazioneBeneficiario=new SoapVar("Comune di Feltre", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->codiceUnitOperBeneficiario=new SoapVar(1031, XSD_STRING, null, null, null, $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->denomUnitOperBeneficiario=new SoapVar("Ragioneria", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->indirizzoBeneficiario=new SoapVar("Piazzetta delle Biade", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->civicoBeneficiario=new SoapVar(1, XSD_STRING, null, null, null, $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->capBeneficiario=new SoapVar("32032", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->localitaBeneficiario=new SoapVar("Feltre", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->provinciaBeneficiario=new SoapVar("BL", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->enteBeneficiario->nazioneBeneficiario=new SoapVar("IT", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaVerificaRPTRisposta->datiPagamentoPA->causaleVersamento="/RFB/990000000007745/0.10";
            ///////////////
            $soapMessage = new SoapVar($soapResponse, SOAP_ENC_OBJECT);

            //Log
            $content["request"] = $soapRequest;
            $content["response"] = json_encode($soapResponse);
            $this->logSmallRequest1($content);
            /////

            return $soapMessage;
        }

        function paaAttivaRPT()
        {

            //Request
            $soapRequest = file_get_contents ('php://input');
            $soapRequestX = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $soapRequest);
            $soapRequestXML = simplexml_load_string($soapRequestX);

            //Response
            $soapResponse = new StdClass;
            $ns="http://www.digitpa.gov.it/schemas/2011/Pagamenti/";

            $esito="OK";
            $soapResponse->paaAttivaRPTRisposta->esito = $esito;

            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->importoSingoloVersamento=178.12;
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->ibanAccredito="IT30N0103076271000001823603";
            $node1 = new SoapVar("G", XSD_STRING, null, null, "tipoIdentificativoUnivoco", $ns);
            $node2 = new SoapVar("00133880252", XSD_STRING, null, null, "codiceIdentificativoUnivoco", $ns);
            $token = new SoapVar(array($node1, $node2), SOAP_ENC_OBJECT, null, null, 'identificativoUnivocoBeneficiario', $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->identificativoUnivocoBeneficiario = new SoapVar($token, SOAP_ENC_OBJECT, null, null, 'identificativoUnivocoBeneficiario', $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->denominazioneBeneficiario=new SoapVar("Comune di Feltre", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->codiceUnitOperBeneficiario=new SoapVar(1031, XSD_STRING, null, null, null, $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->denomUnitOperBeneficiario=new SoapVar("Ragioneria", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->indirizzoBeneficiario=new SoapVar("Piazzetta delle Biade", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->civicoBeneficiario=new SoapVar(1, XSD_STRING, null, null, null, $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->capBeneficiario=new SoapVar("32032", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->localitaBeneficiario=new SoapVar("Feltre", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->provinciaBeneficiario=new SoapVar("BL", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->enteBeneficiario->nazioneBeneficiario=new SoapVar("IT", XSD_STRING, null, null, null, $ns);
            $soapResponse->paaAttivaRPTRisposta->datiPagamentoPA->causaleVersamento="/RFB/990000000007745/0.10";

            $soapMessage = new SoapVar($soapResponse, SOAP_ENC_OBJECT);

            //Database
            try {
                $db = new dbms();
                $insert = $db->query('INSERT INTO paaattivarptcoda (identificativoIntermediarioPA, identificativoStazioneIntermediarioPA, identificativoDominio, identificativoUnivocoVersamento, codiceContestoPagamento, identificativoPSP, statusInviata, importoSingoloVersamento, identificativoIntermediarioPSP, identificativoCanalePSP, statusInviataRT) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    (string)$soapRequestXML->soapenvHeader->pptheadintestazionePPT->identificativoIntermediarioPA,
                    (string)$soapRequestXML->soapenvHeader->pptheadintestazionePPT->identificativoStazioneIntermediarioPA,
                    (string)$soapRequestXML->soapenvHeader->pptheadintestazionePPT->identificativoDominio,
                    (string)$soapRequestXML->soapenvHeader->pptheadintestazionePPT->identificativoUnivocoVersamento,
                    (string)$soapRequestXML->soapenvHeader->pptheadintestazionePPT->codiceContestoPagamento,
                    (string)$soapRequestXML->soapenvBody->pptpaaAttivaRPT->identificativoPSP,
                    0,
                    (double)$soapRequestXML->soapenvBody->pptpaaAttivaRPT->datiPagamentoPSP->importoSingoloVersamento,
                    (string)$soapRequestXML->soapenvBody->pptpaaAttivaRPT->identificativoIntermediarioPSP,
                    (string)$soapRequestXML->soapenvBody->pptpaaAttivaRPT->identificativoCanalePSP,
                    0 );


                $dblog='MYSQL - Esito inserimento paaAttivaRPT: ' . $insert->affectedRows() . ' record.';
                $db->close();
            } catch (Exception $e) {
                $dblog='MYSQL - Errore inserimento paaAttivaRPT: ' . $e->getMessage();
            }
            /////

            //Log
            $content["request"] = $soapRequest;
            $content["response"] = json_encode($soapResponse);
            $content["dblog"] = $dblog;
            $this->logSmallRequest2($content);
            /////


            ///// Invio RPT
            $command = "curl --insecure https://pagopatest.agid.gov.it/cdi/serverPA/PAServerCodaRPT.php";
            shell_exec( $command . "> /dev/null 2>/dev/null &" );
            /////

            ///// Invio RT
            $command = "curl --insecure https://pagopatest.agid.gov.it/cdi/serverPA/PSPServerCodaRT.php";
            shell_exec( $command . "> /dev/null 2>/dev/null &" );
            /////

            return $soapMessage;

        }

        function logSmallRequest1($infos) {
            //Log
            $time = date('d-M-Y');
            $logPath='../uploads/logs/log-PAServer-' . $time . '.txt';
            $log = new logWriter($logPath);
            $helper = new Helper;

            $log->info('=================================================================');
            $log->info('=== SERVER PA Pagamento PSP paaVerificaRPT REQUEST: ' . PHP_EOL . $helper->formatXmlString($infos["request"]));
            $log->info('=================================================================');
            $log->info('=== Risposta del server alla RPT (paaVerificaRPT): ' . PHP_EOL .  $infos["response"] );

        }


        function logSmallRequest2($infos) {
            //Log
            $time = date('d-M-Y');
            $logPath='../uploads/logs/log-PAServer-' . $time . '.txt';
            $log = new logWriter($logPath);
            $helper = new Helper;

            $log->info('=================================================================');
            $log->info('=== SERVER PA Pagamento PSP paaAttivaRPT REQUEST: ' . PHP_EOL . $helper->formatXmlString($infos["request"]));
            $log->info('=================================================================');
            $log->info('=== Risposta del server alla RPT (paaAttivaRPT): ' . PHP_EOL .  $infos["response"] );
            $log->info('=============== DB ===============' . PHP_EOL . $infos["dblog"]);
        }
    }