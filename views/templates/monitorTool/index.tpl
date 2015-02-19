<div class="grid-row">
    <div class="col-12">
        <h1>Monitor</h1>
    </div>
</div>

<div class="grid-row">
    <div class="col-12">
        <ul>

            <li>Monitored Persistence:
                <ul>
                    <?php foreach(get_data('proxyPersistenceMap') as $persistenceName => $config): ?>
                    <li><?php echo $persistenceName; ?>
                        <ul>
                            <?php foreach($config as $key => $value) : ?>
                            <li><?php echo $key . ' = ' . $value; ?></li>
                            <? endforeach; ?>
                        </ul>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li>Output adapter:
                <ul>
                    <?php foreach(get_data('adapters') as $adapterName => $config): ?>
                    <li><?php echo $adapterName; ?>
                        <ul>
                            <?php foreach($config as $key => $value) : ?>
                            <li><?php echo $key . ' = ' . $value; ?></li>
                            <? endforeach; ?>
                        </ul>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        </ul>

    </div>
</div>
