<?php

interface Timber_Command
{
	public function getPayloadSize();

	public function getPayload();

	public function setPayload();
}
