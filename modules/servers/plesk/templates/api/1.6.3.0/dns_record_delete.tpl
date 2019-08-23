<dns>
    <del_rec>
        <filter>
            <?php foreach ($dnsRecords as $dnsRecord): ?>
                <id><?php echo $dnsRecord; ?></id>
            <?php endforeach; ?>
        </filter>
    </del_rec>
</dns>
