<!-- Copyright 1999-2016. Parallels IP Holdings GmbH. -->
<reseller>
    <get>
        <filter>
            <?php foreach($logins as $login): ?>
            <login><?php echo $login; ?></login>
            <?php endforeach; ?>
        </filter>
        <dataset>
            <gen-info/>
            <stat/>
            <limits/>
        </dataset>
    </get>
</reseller>
