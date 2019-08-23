<!-- Copyright 1999-2016. Parallels IP Holdings GmbH. -->
<domain>
    <add>
        <gen_setup>
            <name><?php echo $domain; ?></name>
            <client_id><?php echo $ownerId; ?></client_id>
            <ip_address><?php echo $ipv4Address; ?></ip_address>
            <htype><?php echo $htype; ?></htype>
            <status><?php echo $status; ?></status>
        </gen_setup>
        <hosting>
            <vrt_hst>
                <ftp_login><?php echo $username; ?></ftp_login>
                <ftp_password><?php echo $password; ?></ftp_password>
                <ip_address><?php echo $ipv4Address; ?></ip_address>
            </vrt_hst>
        </hosting>
        <prefs>
            <www>true</www>
        </prefs>
        <template-name><?php echo $planName; ?></template-name>
    </add>
</domain>
