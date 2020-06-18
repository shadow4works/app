<?php

class IpAnonymizer {
	public const NON_ROUTABLE_IPV6 = '::';

	public function anonymizeIp( string $ip ): void {
		$dbw = wfGetDB( DB_MASTER );
		$this->updateTable( $dbw, 'revision', 'rev_user_text', $ip );
		$this->updateTable( $dbw, 'recentchanges', 'rc_user_text', $ip );
		$this->updateTable( $dbw, 'logging', 'log_user_text', $ip );
		$this->updateTable( $dbw, 'archive', 'ar_user_text', $ip );
		$this->updateTable( $dbw, 'filearchive', 'fa_user_text', $ip );
		$this->updateTable( $dbw, 'image', 'img_user_text', $ip );
		$this->updateTable( $dbw, 'oldimage', 'oi_user_text', $ip );
		$this->updateTable( $dbw, 'ipblocks', 'ipb_address', $ip );
		$this->updateTable( $dbw, 'abuse_filter_log', 'afl_user_text', $ip );
		$this->updateTable( $dbw, 'recentchanges', 'rc_title', $ip, [ 'rc_log_type' => [ 'block', 'rights', 'phalanx' ] ] );
		$this->updateTable( $dbw, 'logging', 'log_title', $ip, [ 'log_type' => [ 'block', 'rights', 'phalanx' ] ] );
	}

	private function updateTable( DatabaseMysqli $dbw, string $table, string $column, string $ip, array $conds = [] ): void {
		if ( $dbw->tableExists( $table ) && $dbw->fieldExists( $table, $column ) ) {
			$conds[$column] = $ip;
			$dbw->update(
				$table,
				[$column => self::NON_ROUTABLE_IPV6],
				$conds
			);
		}
	}
}
