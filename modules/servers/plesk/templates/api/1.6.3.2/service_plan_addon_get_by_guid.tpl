<!-- Copyright 1999-2016. Parallels IP Holdings GmbH. -->
<service-plan-addon>
    <get>
        <filter>
            <?php foreach($addonGuids as $guid): ?>
                <guid><?php echo $guid; ?></guid>
            <?php endforeach; ?>
        </filter>
    </get>
</service-plan-addon>
