<!-- Copyright 1999-2016. Parallels IP Holdings GmbH. -->
<webspace>
    <add>
        <gen_setup>
            <name><?php echo $domain; ?></name>
            <owner-id><?php echo $ownerId; ?></owner-id>
            <ip_address><?php echo $ipv4Address; ?></ip_address>
            <htype><?php echo $htype; ?></htype>
            <status><?php echo $status; ?></status>
        </gen_setup>
        <hosting>
            <vrt_hst>
                <property>
                    <name>ftp_login</name>
                    <value><?php echo $username; ?></value>
                </property>
                <property>
                    <name>ftp_password</name>
                    <value><?php echo $password; ?></value>
                </property>
                <ip_address><?php echo $ipv4Address; ?></ip_address>
            </vrt_hst>
        </hosting>
        <prefs>
            <www>true</www>
        </prefs>
        <plan-name><?php echo $planName; ?></plan-name>
    </add>
</webspace>
