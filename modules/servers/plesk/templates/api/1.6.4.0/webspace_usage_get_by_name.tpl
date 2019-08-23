<!-- Copyright 1999-2016. Parallels IP Holdings GmbH. -->
<webspace>
    <get>
        <filter>
            <?php foreach($domains as $domain): ?>
            <name><?php echo $domain; ?></name>
            <?php endforeach; ?>
        </filter>
        <dataset>
            <resource-usage/>
            <limits/>
            <gen_info/>
        </dataset>
    </get>
</webspace>
