<certificate>
    <install>
        <name><?php echo $params['certificateDomain']; ?></name>
        <webspace><?php echo $params['domain']; ?></webspace>
        <content>
            <csr><?php echo $params['csr']; ?></csr>
            <pvt><?php echo $params['key']; ?></pvt>
            <cert><?php echo $params['certificate']; ?></cert>
        </content>
    </install>
</certificate>
