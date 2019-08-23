<certificate>
    <generate>
        <info>
            <bits>2048</bits>
            <country><?php echo $params['country']; ?></country>
            <state><?php echo $params['state']; ?></state>
            <location><?php echo $params['city']; ?></location>
            <company><?php echo $params['orgname']; ?></company>
            <email><?php echo $params['email']; ?></email>
            <name><?php echo $params['domain']; ?></name>
        </info>
    </generate>
</certificate>
