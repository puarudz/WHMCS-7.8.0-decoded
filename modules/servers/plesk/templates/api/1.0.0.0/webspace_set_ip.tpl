<!-- Copyright 1999-2016. Parallels IP Holdings GmbH. -->
<domain>
    <set>
        <filter>
            <name><?php echo $domain; ?></name>
        </filter>
        <values>
            <hosting>
                <vrt_hst>
                    <ip_address><?php echo $ipv4Address; ?></ip_address>
                </vrt_hst>
            </hosting>
        </values>
    </set>
</domain>
