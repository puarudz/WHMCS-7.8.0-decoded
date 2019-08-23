<dns>
    <?php foreach ($mxRecords as $mxRecord => $priority): ?>
        <add_rec>
            <site-id><?php echo $pleskSiteId; ?></site-id>
            <type>MX</type>
            <host/>
            <value><?php echo $mxRecord; ?></value>
            <opt><?php echo $priority; ?></opt>
        </add_rec>
    <?php endforeach; ?>
</dns>
