<?php

require_once("http.php");

// ----------------------------------------------------------
// class Qiniu_RS_GetPolicy

class Qiniu_RS_GetPolicy
{
	public $Expires;

	public function MakeRequest($baseUrl, $mac) // => $privateUrl
	{
		$deadline = $this->Expires;
		if ($deadline == 0) {
			$deadline = 3600;
		}
		$deadline += time();

		$pos = strpos($baseUrl, '?');
		if ($pos !== false) {
			$baseUrl .= '&e=';
		} else {
			$baseUrl .= '?e=';
		}
		$baseUrl .= $deadline;

		$token = Qiniu_Sign($mac, $baseUrl);
		return "$baseUrl&token=$token";
	}
}

function Qiniu_RS_MakeBaseUrl($domain, $key) // => $baseUrl
{
	$keyEsc = rawurlencode($key);
	return "http://$domain/$keyEsc";
}

// --------------------------------------------------------------------------------
// class Qiniu_RS_PutPolicy

class Qiniu_RS_PutPolicy
{
	public $Scope;
	public $CallbackUrl;
	public $CallbackBody;
	public $ReturnUrl;
	public $ReturnBody;
	public $AsyncOps;
	public $EndUser;
	public $Expires;

	public function Token($mac) // => $token
	{
		$deadline = $this->Expires;
		if ($deadline == 0) {
			$deadline = 3600;
		}
		$deadline += time();

		$policy = array('scope' => $this->Scope, 'deadline' => $deadline);
		if (!empty($this->CallbackUrl)) {
			$policy['callbackUrl'] = $this->CallbackUrl;
		}
		if (!empty($this->CallbackBody)) {
			$policy['callbackBody'] = $this->CallbackBody;
		}
		if (!empty($this->ReturnUrl)) {
			$policy['returnUrl'] = $this->ReturnUrl;
		}
		if (!empty($this->ReturnBody)) {
			$policy['returnBody'] = $this->ReturnBody;
		}
		if (!empty($this->AsyncOps)) {
			$policy['asyncOps'] = $this->AsyncOps;
		}
		if (!empty($this->EndUser)) {
			$policy['endUser'] = $this->EndUser;
		}

		$b = json_encode($policy);
		return Qiniu_SignWithData($mac, $b);
	}
}

// ----------------------------------------------------------
// class Qiniu_RS_EntryPath

class Qiniu_RS_EntryPath
{
	public $bucket;
	public $key;
	public function __construct($bucket, $key)
	{
		$this->bucket = $bucket;
		$this->key = $key;
	}
}

// ----------------------------------------------------------
// class Qiniu_RS_EntryPathPair

class Qiniu_RS_EntryPathPair
{
	public $src;
	public $dest;
	public function __construct($src, $dest)
	{
		$this->src = $src;
		$this->dest = $dest;
	}
}

// ----------------------------------------------------------

function Qiniu_RS_Stat($self, $bucket, $key) // => ($statRet, $error)
{
	global $QINIU_RS_HOST;
	return Qiniu_Client_Call($self, $QINIU_RS_HOST . Qiniu_RS_URIStat($bucket, $key));
}

function Qiniu_RS_Delete($self, $bucket, $key) // => $error
{
	global $QINIU_RS_HOST;
	return Qiniu_Client_CallNoRet($self, $QINIU_RS_HOST . Qiniu_RS_URIDelete($bucket, $key));
}


function Qiniu_RS_Move($self, $bucketSrc, $keySrc, $bucketDest, $keyDest) // => $error
{
	global $QINIU_RS_HOST;
	return Qiniu_Client_CallNoRet($self, "$QINIU_RS_HOST".Qiniu_RS_URIMove($bucketSrc, $keySrc, $bucketDest, $keyDest));
}

function Qiniu_RS_Copy($self, $bucketSrc, $keySrc, $bucketDest, $keyDest) // => $error
{
	global $QINIU_RS_HOST;
	return Qiniu_Client_CallNoRet($self, "$QINIU_RS_HOST".Qiniu_RS_URICopy($bucketSrc, $keySrc, $bucketDest, $keyDest));
}


// ----------------------------------------------------------
//batch

function Qiniu_RS_Batch($self, $url) // => ($data, $error)
{
	global $QINIU_RS_HOST;
	return Qiniu_Client_CallWithForm($self, $QINIU_RS_HOST . "/batch?", $url);
}

function Qiniu_RS_BatchStat($self, $entryPaths)
{
	$params = '';
	foreach ($entryPaths as $entryPath) {
		if ($params === '') {
			$params = 'op=' . Qiniu_RS_URIStat($entryPath->bucket, $entryPath->key);
			continue;
		}
		$params .= '&op=' . Qiniu_RS_URIStat($entryPath->bucket, $entryPath->key);
	}
	return Qiniu_RS_Batch($self,$params);
}

function Qiniu_RS_BatchDelete($self, $entryPaths)
{
	$params = '';
	foreach ($entryPaths as $entryPath) {
		if ($params == '') {
			$params = 'op=' . Qiniu_RS_URIDelete($entryPath->bucket, $entryPath->key);
			continue;
		}
		$params .= '&op=' . Qiniu_RS_URIDelete($entryPath->bucket, $entryPath->key);
	}
	return Qiniu_RS_Batch($self, $params);
}

function Qiniu_RS_BatchMove($self, $entryPairs)
{
	$params = '';
	foreach ($entryPairs as $entryPair) {
		if ($params == '') {
			$params = 'op=' . Qiniu_RS_URIMove($entryPair->src->bucket, $entryPair->src->key, $entryPair->dest->bucket, $entryPair->dest->key);
			continue;
		}
		$params .= '&op=' . Qiniu_RS_URIMove($entryPair->src->bucket, $entryPair->src->key, $entryPair->dest->bucket, $entryPair->dest->key);
	}
	return Qiniu_RS_Batch($self, $params);
}

function Qiniu_RS_BatchCopy($self, $entryPairs)
{
	$params = '';
	foreach ($entryPairs as $entryPair) {
		if ($params == '') {
			$params = 'op=' . Qiniu_RS_URICopy($entryPair->src->bucket, $entryPair->src->key, $entryPair->dest->bucket, $entryPair->dest->key);
			continue;
		}
		$params .= '&op=' . Qiniu_RS_URICopy($entryPair->src->bucket, $entryPair->src->key, $entryPair->dest->bucket, $entryPair->dest->key);
	}
	return Qiniu_RS_Batch($self, $params);
}


// ----------------------------------------------------------

function Qiniu_RS_URIStat($bucket, $key) //	=> $entryURIEncoded
{
	return "/stat/" . Qiniu_Encode("$bucket:$key");
}

function Qiniu_RS_URIDelete($bucket, $key)
{
	return "/delete/" . Qiniu_Encode("$bucket:$key");
}

function Qiniu_RS_URICopy($bucketSrc, $keySrc, $bucketDest, $keyDest)
{
	return "/copy/" . Qiniu_Encode("$bucketSrc:$keySrc") . "/" . Qiniu_Encode("$bucketDest:$keyDest");
}

function Qiniu_RS_URIMove($bucketSrc, $keySrc, $bucketDest, $keyDest)
{
	return "/move/" . Qiniu_Encode("$bucketSrc:$keySrc") . "/" . Qiniu_Encode("$bucketDest:$keyDest");
}
