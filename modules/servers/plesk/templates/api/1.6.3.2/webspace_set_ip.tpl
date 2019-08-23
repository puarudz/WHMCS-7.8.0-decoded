<!-- Copyright 1999-2016. Parallels IP Holdings GmbH. -->
<webspace>
    <set>
        <filter>
            <name><?php echo $domain; ?></name>
        </filter>
        <values>
            <hosting>
                <vrt_hst>
                    <?php if (!empty($ipv4Address)): ?>
                        <ip_address><?php echo $ipv4Address; ?></ip_address>
                    <?php endif; ?>
                    <?php if (!empty($ipv6Address)): ?>
                        <ip_address><?php echo $ipv6Address; ?></ip_address>
                    <?php endif; ?>
                </vrt_hst>
            </hosting>
        </values>
    </set>
</webspace>
